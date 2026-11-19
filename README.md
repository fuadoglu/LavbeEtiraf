# Lavbe · Etiraflar Vadisi

**Lavbe telefondan anonim etiraf yazmaq, öz dilində etirafları oxumaq və hekayələrin davamını izləmək üçün mətn platformasıdır.** Məhsulun ünvanı [lavbe.com](https://lavbe.com), development reposu [LavbeEtiraf](https://github.com/fuadoglu/LavbeEtiraf)-dır.

[Codex ilə başla](#codex-ilə-başla) · [Məhsulun quruluşu](#məhsulun-quruluşu) · [Sənəd xəritəsi](#sənəd-xəritəsi) · [Yoxlamalar](#yoxlamalar) · [Buraxılış](#buraxılış)

## Cari vəziyyət

| Hissə | Vəziyyət |
|---|---|
| Məhsul və texniki spesifikasiya | **Hazırdır.** Əsas qərarlar və 27 qəbul ssenarisi repodadır. |
| UI atlası | **Hazırdır.** 18 ekranın mobil və masaüstü görünüşləri, gündüz/gecə və əsas vəziyyətləri yoxlamaq mümkündür. |
| Dil kataloqu | **Baxışa hazır ilkin mətnlər.** 17 dil, 12 kateqoriya və 204 kateqoriya etiketi. Tətbiq paketlərini Codex yazır, yekun mətnlərə məhsul sahibi baxır. |
| Laravel tətbiqi | **LAV-001 PR #4 açıqdır.** Laravel 13/Blade əsası, MySQL smoke testi və CI hazırdır. |
| Ortaq UI komponentləri | **[LAV-002](https://github.com/fuadoglu/LavbeEtiraf/issues/5) icradadır.** İş həcmi backlog və UI-01/UI-04 qəbul meyarlarına əsaslanır. |
| İctimai buraxılış | **Ayrı mərhələdir.** [Buraxılış meyarları](docs/delivery/operations.md) staging, shared hosting, hüquqi təsdiqlər və xarici servislərin doğrulanmasını tələb edir. |

## Codex ilə başla

1. `main`-dən işə başla, [AGENTS.md](AGENTS.md), [backlog](docs/delivery/backlog.md) və [qəbul matrisi](docs/delivery/acceptance.md) fayllarını oxu.
2. [LAV-001](https://github.com/fuadoglu/LavbeEtiraf/issues/1) üzrə `BAS-01` qəbul meyarını götür. [Arxitektura](docs/architecture/system.md) shared hosting sərhədlərini müəyyən edir.
3. [Lokal yoxlamaları](#yoxlamalar) işlə. Ayrı branch-də icra et, commit və push et, issue-ya bağlı PR aç. PR-a faktiki əmr və nəticələri əlavə et.

İlk Codex tapşırığı üçün mətn:

> [LAV-001](https://github.com/fuadoglu/LavbeEtiraf/issues/1) işini icra et. Əvvəl `AGENTS.md`, arxitektura, backlog və `BAS-01` meyarını oxu. Laravel 13, Blade, nümunə mühit faylı və MySQL/MariaDB smoke testi ilə shared hostingə uyğun baza qur. GitHub Actions-da tətbiq yoxlamalarını mövcud sənəd yoxlamaları ilə birləşdir. Faktiki işlətdiyin qurulum və test əmrlərini README-yə əlavə et. Branch-i push et və issue-ya bağlı PR-da yoxlama nəticələrini göstər.

## Məhsulun quruluşu

| Sahə | Lavbe-nin davranışı |
|---|---|
| **Oxu və kəşf** | Ana lent və kateqoriyalar bir lent şablonundan qurulur. İlk 12 kart serverdən HTML kimi gəlir. **Daha çox** sabit cursor ilə davam edir, adi HTML linki də işləyir. |
| **Anonim yazı** | 18+ istifadəçi qonaq kimi etiraf və rəy yaza bilər. Açıq ad `Lavbe #XXXXXXXX` formasındadır və yalnız həmin etiraf daxilində iştirakçını tanıdır. |
| **Öz məzmununu idarə et** | İstəyə bağlı cihaz yaddaşı, məxfi bərpa kodu və e-poçt hesabı. Etirafın açıq linki sahiblik hüququ vermir. |
| **Rəy və moderasiya** | Rəy ayrıca reklamsız səhifədə yazılır. Etiraf və rəy üçün şikayət ayrıdır. Admin paneli **Etiraflar · Rəylər · Şikayətlər** bölmələrindən ibarətdir. |
| **Davam və bildiriş** | Müəllifin **Sonra nə oldu?** mətni eyni etirafın altında yayımlanır. İzləyən oxucular seçdikləri kanalla neytral bildiriş alırlar. |
| **Dil və görünüş** | 17 dil, 12 kateqoriya, axtarışlı dil menyusu, yumru bayraqlar, dilin öz adı və ingiliscə adı. Saxlanmış seçim cihaz və ölkə siqnalından üstündür. Seçilmiş lent yalnız həmin dildədir. |
| **PWA və performans** | Gündüz/gecə rejimi, ana ekrana əlavə etmə, statik oflayn görünüş və ayrıca seçilən qaralama yaddaşı. Etiraf və şəxsi məlumat PWA keşinə yazılmır. |
| **SEO və monetizasiya** | Hər təsdiqlənib yayımlanan etiraf orijinal dilində sabit URL və sitemap qeydi alır. AdSense yeganə monetizasiyadır. Uyğun oxu detalında bir reklam yeri, razılıqdan sonra sanitizasiya olunmuş GA4 ölçümü. |

**Texniki hədəf:** PHP 8.5 üstün seçim, 8.4 uyğun alternativ, Laravel 13, Blade, MySQL/MariaDB, yerli Lucide ikonları, az JavaScript və qısa cron işləri. İstehsal shared hostingdə PHP və verilənlər bazası ilə işləyir. Node yalnız development yoxlamaları və ehtiyac olduqda aktivlərin əvvəlcədən hazırlanması üçündür.

## Tətbiqin lokal qurulması

PHP 8.4+, Composer 2, `pdo_mysql`, MySQL 8.0+ və ya MariaDB 10.6+ tələb olunur. Əvvəl lokal MySQL-də boş `lavbe` bazası və məhdud hüquqlu istifadəçi yarat. Repo daxilində:

```bash
composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate
npm ci --ignore-scripts
npm run build
```

`.env`-də `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` və `DB_PASSWORD` dəyərlərini lokal bazana uyğun doldur. Vite aktivləri development kompüterində yığılır; istehsalda Node prosesi işlətmək tələb olunmur. Sonra:

```bash
php artisan migrate
composer test
php artisan serve
```

Ana səhifə `http://127.0.0.1:8000`, Laravel health endpoint-i isə `http://127.0.0.1:8000/up` ünvanındadır. Cari iş əsas Blade layout-u və UI komponentlərini qurur; məlumat və məhsul axınları backlog üzrə sonrakı işlərdə əlavə olunur.

## Sənəd xəritəsi

| Axtardığın qərar | İstinad |
|---|---|
| İstifadəçi axınları, rollar və məhsul sərhədləri | [Məhsul](docs/product.md) |
| Palitra, komponentlər, mobil UI və ekran vəziyyətləri | [Dizayn sistemi](docs/ui/design-system.md) · [Ekran spesifikasiyası](docs/ui/screens-and-states.md) · [HTML atlası](docs/ui/wireframes.html) |
| Lent, səhifələmə, keş və daxili axtarış | [Lent müqaviləsi](docs/architecture/feed-contract.md) |
| Dil paketləri və sabit kateqoriya açarları | [Lokalizasiya](docs/localization.md) · [Kataloq](spec/catalog.json) · [Dil paketi müqaviləsi](spec/locale-contract.json) |
| Sahiblik, paralel dəyişikliklər və silinmə | [Sahiblik və yayım](docs/architecture/ownership-and-publication.md) |
| Moderasiya, məxfilik və hüquqi səhifələr | [Moderasiya və məxfilik](docs/moderation-privacy.md) |
| Canonical, sitemap, IndexNow, AdSense və GA4 | [SEO və monetizasiya](docs/seo-ads-analytics.md) · [Reklam və məxfilik matrisi](docs/architecture/traffic-and-privacy.md) |
| Texniki quruluş, cron, PWA və shared hosting | [Arxitektura](docs/architecture/system.md) · [İstismar](docs/delivery/operations.md) |
| Sprintlər, asılılıqlar və qəbul sübutu | [Backlog](docs/delivery/backlog.md) · [Qəbul matrisi](docs/delivery/acceptance.md) |

Qərarın sahibi cədvəldəki əsas sənəddir. README layihəyə girişdir. Davranış dəyişikliyi olanda aidiyyəti əsas sənəd və qəbul meyarı eyni PR-da yenilənir.

## Yoxlamalar

Təmiz checkout-da sənəd, kataloq və atlas məntiqini yoxlamaq üçün Python 3 və Node.js kifayətdir:

```bash
git clone https://github.com/fuadoglu/LavbeEtiraf.git
cd LavbeEtiraf
python3 scripts/check_docs.py
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s scripts
node scripts/check_wireframes.mjs
```

Laravel testlərindən əvvəl Vite aktivləri `npm ci --ignore-scripts && npm run build` ilə yığılır. `composer test` SQLite in-memory test mühitində unit/feature sınaqlarını işlədir. MySQL/MariaDB inteqrasiya testi ayrıca qoşulur. Testə ayrılmış DB-də migrations icra edildikdən sonra:

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=lavbe DB_USERNAME=lavbe DB_PASSWORD='local-test-password' composer test:mysql
```

`composer test:mysql` yalnız MySQL smoke testini işlədir. GitHub Actions `Application` job-u MySQL 8.4 servisi başladır, Composer və npm lock fayllarından asılılıqları quraşdırır, statik frontend aktivlərini yığır, migrations və tətbiq testlərini icra edir. Mövcud `Documentation` workflow-u ayrıca saxlanılıb.

## LAV-002 lokal yoxlama qeydi

2026-11-19: `npm ci --ignore-scripts && npm run build` keçdi; CSS bundle 15.2 KB, JavaScript 0.86 KB oldu. `composer test` 6 test/18 assertion, `composer test:mysql` 1 test/3 assertion keçdi. Səhifə baxışı mobil ölçüdə ana lent, gündüz/gecə, kateqoriya, 404 və admin mock-u əhatə etdi; modal Escape ilə bağlandı və fokus açan düyməyə qayıtdı. Admin mock yalnız `local`/`testing` mühitində açılır və sintetik məlumat göstərir.

İstəyə bağlı real brauzer yoxlaması üçün Playwright və Chromium olan development mühitində:

```bash
python3 scripts/check_wireframes_browser.py
```

Sistem Chromium-u istifadə etmək üçün `--chromium /path/to/chromium` verilə bilər. Vizual baxış üçün [atlas faylını](docs/ui/wireframes.html) lokal brauzerdə aç. Tətbiq dili paketləri LAV-010-da yaradıldıqdan sonra `python3 scripts/check_docs.py --packages resources/lavbe/locales` də icra olunur.

## GitHub iş axını

**Issue → branch → commit və push → PR → yoxlamalar və baxış → merge.**

- İşin ID-si, asılılıqları və qəbul ssenarisi [backlog](docs/delivery/backlog.md) və issue ilə bağlıdır. Branch formatı `codex/lav-<n>-<mövzu>`-dur.
- Hər PR istifadəçi davranışındakı dəyişikliyi, qəbul ID-sini, işlədilmiş əmri və nəticəni göstərir. Sprintlər işlək daxili artım verir.
- Real etiraf, rəy, şikayət, e-poçt, bərpa kodu və istehsal sirri repo, issue, PR və test nümunələrinə daxil edilmir.

## Buraxılış

Tam ictimai buraxılış üçün real shared hosting, məlumatın yeri və operator kimliyi, 17 dil üzrə mətn baxışı və moderator təminatı, SEO və razılıq sınaqları, backup və bərpa üzrə sübut tələb olunur. AdSense aktivləşdirməsi üçün domen, `ads.txt` və razılıq yoxlaması ayrıca tamamlanır. [Buraxılış və geri dönüş qaydaları](docs/delivery/operations.md)

## LAV-001 lokal yoxlama qeydi

2026-11-19 macOS development mühitində PHP 8.4.10, Composer 2.8.10 və MariaDB 12.1.2 ilə işlədildi. Təmiz Laravel install-dan Composer lock `laravel/framework` v13.43.0 versiyasını seçdi. `php artisan migrate --force` keçdi; MySQL smoke testi 1 test/3 assertion, Laravel suite 3 test/7 assertion, `/up` sorğusu HTTP 200 qaytardı. `python3 scripts/check_docs.py` 15 sənəd və 27 qəbul ID-sini yoxladı, unittest 7 test keçirdi, atlas yoxlaması 18 ekran/180 UI kombinasiyasını yoxladı. Təmiz GitHub Actions [Application run](https://github.com/fuadoglu/LavbeEtiraf/actions/runs/44426405266) MySQL 8.4 migration, Laravel suite və smoke testini, [Documentation run](https://github.com/fuadoglu/LavbeEtiraf/actions/runs/44426405252) isə sənəd workflow-unu keçirdi.
