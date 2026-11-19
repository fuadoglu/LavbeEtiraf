<?php

namespace App\Http\Controllers;

use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    public function index(LocaleRegistry $locales): StreamedResponse
    {
        $base = rtrim((string) config('app.url'), '/');
        $localesList = $locales->all();

        return response()->stream(function () use ($localesList, $base): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($localesList as $locale) {
                foreach (range(1, $this->pageCount($locale['segment'])) as $page) {
                    $path = '/'.$locale['segment'].'/sitemap-'.$page.'.xml';
                    echo '<sitemap><loc>'.$this->xml($base.$path).'</loc></sitemap>';
                }
            }
            echo '</sitemapindex>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, no-cache, must-revalidate']);
    }

    public function locale(string $locale, int $page, LocaleRegistry $locales): StreamedResponse
    {
        abort_unless($locales->bySegment($locale), 404);
        abort_unless($page >= 1 && $page <= $this->pageCount($locale), 404);
        $categories = $this->eligibleCategories($locale, $locales);
        $alternates = $locales->all();
        $base = rtrim((string) config('app.url'), '/');
        $perPage = $this->confessionPageSize();
        $offset = ($page - 1) * $perPage;
        $rows = $this->publicQuery($locale)->orderBy('id')->offset($offset)->limit($perPage)->get([
            'id', 'public_id', 'public_updated_at', 'published_at',
        ]);

        return response()->stream(function () use ($locale, $categories, $alternates, $base, $rows, $page): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
            if ($page === 1) {
                $this->writeAlternateUrl($base.'/'.$locale.'/', $alternates, $base);
                foreach ($categories as $category) {
                    $categoryAlternates = $this->categoryAlternates($category['key'], $alternates);
                    $this->writeAlternateUrl($base.'/'.$locale.'/topics/'.$category['key'], $categoryAlternates, $base, $category['key']);
                }
            }
            foreach ($rows as $row) {
                $path = '/'.$locale.'/confession/'.$row->public_id;
                $lastmod = $row->public_updated_at ?: $row->published_at;
                echo '<url><loc>'.$this->xml($base.$path).'</loc>';
                if ($lastmod) {
                    echo '<lastmod>'.$this->xml(Carbon::parse($lastmod)->toAtomString()).'</lastmod>';
                }
                echo '</url>';
            }
            echo '</urlset>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, no-cache, must-revalidate']);
    }

    public function robots(): IlluminateResponse
    {
        $base = rtrim((string) config('app.url'), '/');
        $body = "User-agent: *\nDisallow: /*/my/\nDisallow: /*/moderation\nDisallow: /*/search\nDisallow: /*/feed-fragment\nSitemap: ".$base."/sitemap.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    private function writeAlternateUrl(string $url, array $locales, string $base, ?string $category = null): void
    {
        echo '<url><loc>'.$this->xml($url).'</loc>';
        foreach ($locales as $locale) {
            $path = '/'.$locale['segment'].'/'.($category === null ? '' : 'topics/'.$category);
            echo '<xhtml:link rel="alternate" hreflang="'.$this->xml($locale['code']).'" href="'.$this->xml($base.$path).'"/>';
        }
        $fallback = collect($locales)->firstWhere('code', 'en');
        if ($fallback) {
            echo '<xhtml:link rel="alternate" hreflang="x-default" href="'.$this->xml($base.'/'.$fallback['segment'].'/'.($category === null ? '' : 'topics/'.$category)).'"/>';
        }
        echo '</url>';
    }

    /** @return list<array{key: string}> */
    private function eligibleCategories(string $locale, LocaleRegistry $locales): array
    {
        $minimum = (int) config('seo.category_min_public_items', 5);
        $eligible = [];
        foreach ($locales->catalog()['categories'] as $category) {
            $count = $this->categoryPublicCount($locale, $category['key']);
            if ($count >= $minimum) {
                $eligible[] = ['key' => $category['key']];
            }
        }

        return $eligible;
    }

    private function categoryAlternates(string $category, array $locales): array
    {
        $minimum = (int) config('seo.category_min_public_items', 5);

        return array_values(array_filter($locales, fn (array $locale): bool => $this->categoryPublicCount($locale['segment'], $category) >= $minimum));
    }

    private function categoryPublicCount(string $locale, string $category): int
    {
        return $this->publicQuery($locale)->where('category_key', $category)->count();
    }

    private function pageCount(string $locale): int
    {
        $count = $this->publicQuery($locale)->count();

        return max(1, (int) ceil($count / $this->confessionPageSize()));
    }

    private function confessionPageSize(): int
    {
        return max(1, min(49000, (int) config('seo.sitemap_max_urls', 50000) - 32));
    }

    private function publicQuery(string $locale): Builder
    {
        return DB::table('confessions')
            ->where('confessions.locale', $locale)
            ->where('confessions.status', 'public')
            ->whereNotNull('confessions.published_at')
            ->whereNotNull('confessions.public_revision_id')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')->from('confession_revisions')
                    ->whereColumn('confession_revisions.id', 'confessions.public_revision_id')
                    ->whereColumn('confession_revisions.confession_id', 'confessions.id')
                    ->where('confession_revisions.status', 'approved');
            });
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
