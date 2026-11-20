# SEO, AdSense və analitika

İcra səviyyəsində [lent müqaviləsi](architecture/feed-contract.md) və [səth, reklam və analitika matrisi](architecture/traffic-and-privacy.md) tətbiq olunur.

## İctimai URL və indeks müqaviləsi

| Resurs | URL nümunəsi | Qayda |
|---|---|---|
| Dil lenti | `/ru/` | Dilə məxsus HTML, daxili keçid və öz canonical ünvanı |
| Kateqoriya | `/ru/topics/semya` | Eyni dil və sabit kateqoriyaya bağlı yerli slug, kifayət qədər məzmun varsa indeksə açıq |
| Etiraf | `/ru/confession/7k4m2` | Orijinal dil, sabit opaque ID, 200, öz canonical ünvanı, tam HTML |
| Növbəti lent səhifəsi | `/ru/?page=2` | Standart Yeni axınında ayrıca botların izləyə bildiyi HTML və öz canonical ünvanı |
| Axtarış və sıralama kombinasiyası | `/ru/?sort=weekly` | Sitemap xaricində, indeksdən kənar saxlanır. Əsas kateqoriya və etiraf linkləri standart lentdən əlçatandır. |

URL şablonları sabitdir. Yazılan başlıq URL-ə salınmır. Dil seqmenti orijinal məzmunu saxlayır. Səhv dil və ya dəyişmiş slug variantı təhlükəsizdirsə əsas ünvana yönləndirilir. Query parametrlərində şəxsi mətn daşınmır. İctimai lokal link birbaşa açılır və cihaz dili üzrə yönləndirilmir. `hreflang` yalnız qarşılıqlı uyğun səhifələr üçün tam və qarşılıqlı olur. `x-default` uyğun olduqda sabit ingilis fallback səhifəsinə işarə edir. Müstəqil etiraflar bir-birinin tərcüməsi kimi işarələnmir.

LAV-006 detail endpoint-i yalnız `status=public`, `published_at` və approved `public_revision_id` birlikdə doğrulananda məzmun qaytarır. Başlıq və mətn Blade escaping ilə ilkin HTML-də çıxır. Canonical domain `APP_URL`-dən qurulur; Host header-dən götürülmür. Detail və sitemap `public, no-cache, must-revalidate` qaytarır: ara keşlər obyekt saxlaya bilər, amma hər istifadədən əvvəl origin statusunu yoxlamalıdır. Gizli/geri çəkilmiş resurs 404, əvvəl ictimai olmuş terminal silinmə 410-dur. `robots.txt` sitemap index-i göstərir və şəxsi yolları crawler üçün məhdudlaşdırır; robots qaydası təkbaşına giriş nəzarəti sayılmır.

HTML başlığı, H1 və təsvir təsdiqlənmiş versiyanı əks etdirir. Metadata və paylaşım önizləməsi moderasiya qərarına daxildir. Google fraqment və başlığı seçə bilər. Kateqoriya sitemap-ə ilkin olaraq həmin dildə beş təsdiqlənmiş etirafla namizəd olur, hədd konfiqurasiya olunur və boş səhifə indeksə açılmır. Fərdi etirafın indeks uyğunluğu kateqoriyanın həddindən asılı deyil. Spam və təsdiqlənməmiş versiya açıq görünmür.

Sitemap indeksini domen kökündə təqdim et. Dil üzrə sabit hissələr yalnız 200 qaytaran canonical URL-ləri saxlayır. Əhəmiyyətli təsdiqlənmiş dəyişiklik `lastmod` dəyərini yeniləyir. Reaksiya, təsadüfi tarix və baxış sayı onu dəyişmir. Geri çəkilmiş URL dərhal siyahıdan çıxır. `robots.txt` indeksə işarə edir və silinmiş ictimai URL-nin statusunu crawler-dən gizlətmir. Google Search Console domen yoxlaması və sitemap təqdimatı bir dəfə edilir. Bing Webmaster Tools və IndexNow yalnız əlavə/dəyişən/silinən URL-lər üçün outbox ilə işləyir. Etiraf üçün Google Indexing API istifadə olunmur. Faktiki indeks və sıralama xarici nəticədir.

### Xarici axtarış bildirişləri və yoxlama qeydi

Moderasiya qərarı sitemap-in cari committed vəziyyətini dəyişir və `moderation_outbox` event-i eyni DB transaction-da yazır. `lavbe:indexnow` yalnız `publish`, `hide`, `restore` və `terminal_delete` event-lərini götürür; uşaqların şəxsi fəaliyyət event-ləri axtarış provider-inə çıxmır. Bildiriş URL-i `APP_URL`-in HTTPS host-u, event locale-i və opaque public ID-dən qurulur; əvvəllər açıq olmamış resurs göndərilmir. Gizlədilmiş/silinmiş, əvvəllər açıq URL yenə bildirilir ki, provider yenilənmiş statusu görə bilsin. IndexNow 200/202 cavabı yalnız qəbul kimi qeydə alınır, indekslənmə kimi yox.

`INDEXNOW_KEY` 8–128 ASCII hərf/rəqəm/defis olur. Açıq `/{key}.txt` route yalnız key konfiqurasiyada olduqda həmin mətni verir. Sirlər və provider cavab gövdəsi loglanmır. `INDEXNOW_MAX_ATTEMPTS` default 8-dir; 429, 5xx və bağlantı xətası artan gecikmə ilə təkrar olunur, daimi 4xx və retry limiti audit cədvəlində sabit kodla saxlanır. Hər event üçün unikal delivery sətri və claim müddəti eyni outbox-un paralel cron tərəfindən iki dəfə göndərilməsini azaldır. Cron mövcud Laravel scheduler ilə hər dəqiqə çağırılır; hosting `php /path/to/app/artisan schedule:run` işlətməlidir.

Production operator yoxlaması:

1. HTTPS production `APP_URL` və domenin Search Console/Bing Webmaster Tools property-lərində sahiblik doğrulamasını təsdiqlə.
2. `INDEXNOW_KEY`-i deployment secret-ə yaz, `/{key}.txt` URL-sinin dəqiq key mətni ilə HTTP 200 qaytardığını səlahiyyətli mühitdən yoxla. Key-i issue, log və ya screenshot-a köçürmə.
3. Təsdiqlənmiş sitemap URL-sini hər iki webmaster property-yə əlavə et və sitemap statusunu, yoxlama vaxtını və property host-unu operatorun özəl buraxılış qeydində saxla.
4. Staging-də sintetik dərc/gizlət event-i yaradıb `lavbe:indexnow` cron nəticəsini, provider cavab kodunu və həmin URL-nin sitemap/HTTP statusunu tutuşdur. Canlı IndexNow qəbulu indeksə daxil olma zəmanəti deyil.
5. Google üçün Search Console sitemap təqdimatı və URL Inspection yalnız təsdiqlənmiş property hesabı ilə aparılır. Etiraf URL-ləri üçün Google Indexing API istifadə edilmir.

Bu repo Search Console və Bing Webmaster hesab sirlərini saxlamır və onların canlı property auditini avtomatik iddia etmir. Rəsmi Google qaydasına görə sitemap göndərişi crawl üçün göstərişdir, zəmanət deyil. IndexNow bulk formatı host, key və URL siyahısını qəbul edir; 202 cavabı açar yoxlanışı davam edərkən qəbul edilmiş bildiriş ola bilər. Bing-in legacy SOAP/POX endpoint-ləri 2026-08-31 tarixində retirement planındadır; yeni inteqrasiya onlara bağlanmır.

Sitemap root index `/sitemap.xml` hər locale üçün 50,000 URL limitini aşmayan bölmələri sadalayır (`/{locale}/sitemap-{page}.xml`). Locale sitemaps home, ən azı beş cari ictimai etirafı olan kateqoriyalar və təsdiqlənmiş canlı revision-a bağlı confession URL-lərini ehtiva edir. `public_updated_at` yalnız təsdiqlənmiş mətn revision-ı dəyişəndə yenilənir; gizlətmə, bərpa və sırf audit yeniləməsi lastmod-u saxtalaşdırmır. Hreflang yalnız home və eyni kateqoriya reyestr səhifələrinə yazılır.

## AdSense

Yeganə monetizasiya AdSense-dir. Yayım statusu və reklam uyğunluğu ayrı saxlanır. Uyğunluq seçilən dil, etiraf, davam və bütün açıq rəylər üzrə hesablanır. Məzmun versiyası dəyişəndə qiymətləndirmə və keş yenilənir. Naməlum uyğunluq, dil siyasətində dəstəksiz dil, cari razılıq konfiqurasiyası çatışmazlığı və ya qapalı qlobal açar reklamsız görünüş verir. Azərbaycanca əsas məzmun reklamsızdır. Rus dilinə reklam uyğunluğu və Rusiya regionunda xidmət məhdudiyyəti ayrı konfiqurasiya olunur.

İlkin variant uyğun oxu detalında tam mətnin sonundan sonra bir idarə olunan responsive blokdur. Yazma, ayrıca rəy forması, hesab, kömək/hüquqi, axtarış, boş və aşağı dəyərli səth, lent və oflayn görünüş reklamsızdır. Hərəkət düymələrindən vizual məsafə, rezerv sahə, normal mətn axını. Auto Ads, örtən reklam və avtomatik refresh söndürülür. Yaş, həssas məzmun və regional razılıq siyasəti ayrıca yoxlanır. `ads.txt` yalnız operator tərəfindən təsdiqlənmiş identifikatorla çıxır. Qlobal açar və səhifə səviyyəli dayandırma görünən kodu və cache variantını birlikdə dəyişir. AdSense/CMP xarici aktivləşdirmə buraxılışın reklam qapısıdır.

Active View, AdSense Page RPM, min oxu sessiyasına gəlir, uyğun trafik payı, ikinci etirafa keçid, geri qayıtma və etibarsız trafik siqnalları dil və ölkə üzrə izlənir. Yalnız ilkin model ölçüldükdən sonra bir yerləşim sınağı təsdiqlənə bilər.

## GA4 və razılıq

Bir istehsal GA4 mülkiyyəti, ayrılmış sınaq mühiti. Basic Consent Mode. Analitika seçimi verilmədən GA skripti və sorğusu yüklənmir. Reklam razılığı ayrıca idarə olunur. Yalnız uyğun ictimai oxu səthində əvvəlcədən icazəli hadisələr: clean page view, next confession, dil dəyişikliyi. Parametrlər locale, səhifə növü və sanitizasiya olunmuş ümumi yol ilə məhdudlaşır. Xam URL, referrer və title məruz qalması şəbəkə sınağında yoxlanır. Avtomatik page view, sayt axtarışı və riskli Enhanced Measurement funksiyaları söndürülür. Qonaq kodu, etiraf ID-si və mətn parametri ötürülmür. Şəxsi, yazı, şikayət, bərpa, admin və oflayn axınlar analitikadan kənardır. Google Search Console gedən klik və indeks siqnallarını URL prefiksi ilə, GA4 oxu davranışını content_locale ilə göstərir. Canlı açarlar mühit konfiqurasiyasından gəlir.

Rəsmi mənbələr: [Google çoxdilli saytlar](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites) · [sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap) · [Google recrawl](https://developers.google.com/search/docs/crawling-indexing/ask-google-to-recrawl) · [IndexNow protokolu](https://www.indexnow.org/documentation) · [IndexNow FAQ](https://www.indexnow.org/faq) · [Bing Webmaster API](https://learn.microsoft.com/en-us/bingwebmaster/) · [AdSense yerləşimi](https://support.google.com/adsense/answer/1346295) · [UGC](https://support.google.com/adsense/answer/1355699) · [məzmuna uyğun dillər](https://support.google.com/adsense/answer/9727) · [CMP](https://support.google.com/adsense/answer/13554116) · [GA və PII](https://support.google.com/analytics/answer/6366371) · [Consent Mode](https://developers.google.com/tag-platform/security/concepts/consent-mode)

Outbox çatdırılmasının işlək limitləri config/delivery.php faylındadır. IndexNow batch ölçüsü və maksimumu, bağlantı/sorğu timeout-u, claim müddəti və retry gecikməsi burada idarə olunur. Standart batch 100, maksimum 500-dür. lavbe:indexnow --limit parametrsiz çağırılanda config ölçüsünü istifadə edir. Hesab bildirişlərinin outbox batch-i, e-poçt batch-i və e-poçt claim müddəti eyni faylın notifications bölməsindədir. Komandalar --limit ilə batch ölçüsünü müvəqqəti dəyişə bilər.
