# Lavbe · Etiraflar Vadisi

**Lavbe telefondan anonim etiraf yazmaq, öz dilində etirafları oxumaq və hekayələrin davamını izləmək üçün mətn platformasıdır.** Məhsulun ünvanı [lavbe.com](https://lavbe.com), development reposu [LavbeEtiraf](https://github.com/fuadoglu/LavbeEtiraf)-dır.

[Codex ilə başla](#codex-ilə-başla) · [Məhsulun quruluşu](#məhsulun-quruluşu) · [Sənəd xəritəsi](#sənəd-xəritəsi) · [Yoxlamalar](#yoxlamalar) · [Buraxılış](#buraxılış)

## Cari vəziyyət

| Hissə | Vəziyyət |
|---|---|
| Məhsul və texniki spesifikasiya | **Hazırdır.** Əsas qərarlar və 39 qəbul ssenarisi repodadır. |
| UI atlası | **Hazırdır.** 18 ekranın mobil və masaüstü görünüşləri, gündüz/gecə və əsas vəziyyətləri yoxlamaq mümkündür. |
| Dil kataloqu | **Baxışa hazır ilkin mətnlər.** 17 dil, 12 kateqoriya və 204 kateqoriya etiketi. Tətbiq paketlərini Codex yazır, yekun mətnlərə məhsul sahibi baxır. |
| Laravel tətbiqi | **LAV-001 tamamlanıb.** Laravel 13/Blade əsası, MySQL smoke testi və CI `main`-dədir. |
| Ortaq UI komponentləri | **LAV-002 tamamlanıb.** UI-01/UI-04 baza komponentləri və sübutları `main`-dədir. |
| Dil reyestri və lent | **LAV-003 tamamlanıb.** UI-02/LOC-01: dil seçimi, feed read model, 12 kartlıq HTML səhifələmə, cursor, axtarış və 17 dil üzrə ilkin mətnlər `main`-dədir. |
| Qonaq etirafı və sahiblik | **LAV-004 tamamlanıb.** Mətn forması, moderator baxışına gizli göndəriş, qonaq sahibliyi, birdəfəlik bərpa və 17 dil üçün yazı axını `main`-dədir. |
| Moderasiya və dərc keçidləri | **LAV-005 tamamlanıb.** MFA, dil üzrə moderator icazələri, versiyalı qərarlar, audit/outbox və sahibin geri çəkmə axını `main`-dədir. |
| Açıq etiraf və texniki SEO | **LAV-006 tamamlanıb.** Təsdiqlənmiş revision-a bağlı detail, canonical, locale sitemap və məzmun lastmod-u `main`-dədir. |
| Rəy, müəllif cavabı, davam və dəstək | **[LAV-007](https://github.com/fuadoglu/LavbeEtiraf/issues/16) tamamlanıb.** Pending-only rəy və davam, sahiblik/bərpa, moderator növbəsi, support reaksiyası və mətnsiz notification outbox-u [PR #17](https://github.com/fuadoglu/LavbeEtiraf/pull/17) ilə `main`-dədir. |
| Şikayət, hüquqi bildiriş və etiraz | **[LAV-008](https://github.com/fuadoglu/LavbeEtiraf/issues/20) tamamlanıb.** Ayrı hədəf/kanal qrupları, server prioriteti, şifrəli izah, təzə MFA növbəsi, tək sahib etirazı və auditli bərpa [PR #21](https://github.com/fuadoglu/LavbeEtiraf/pull/21) ilə `main`-dədir. |
| Hesab, Mənim bölməsi və bildirişlər | **[LAV-009](https://github.com/fuadoglu/LavbeEtiraf/issues/22) icra olunur.** Email təsdiqi, parol bərpası, guest sahiblik iddiası, user-scope izləmə və opt-in neytral bildirişlər bu branch-də hazırlanıb; PR/CI mərhələsi qalır. |
| İctimai buraxılış | **Ayrı mərhələdir.** [Buraxılış meyarları](docs/delivery/operations.md) staging, shared hosting, hüquqi təsdiqlər və xarici servislərin doğrulanmasını tələb edir. |
| Oxucu kəşfi və müəllif adı | **LAV-030 hazırlanıb.** Başlıqda dəstək ürəyi, dəstəklənənlərin private siyahısı, etiraf tövsiyələri, məcburi başlıq və istəyə bağlı açıq ad-soyad PR/CI mərhələsindədir; hüquqi səhifələr ortaq footer-dən açılır. |
| Etirafdan şikayət | **LAV-031 hazırlanıb.** İcma şikayəti və hüquqi bildiriş etiraf səhifəsində accessible dialog popup-da açılır; CI mərhələsindədir. |
| Popup responsivliyi | **LAV-032 hazırlanır.** Dialog eni, daxili skrollu və mobil safe-area boşluqları yenilənir; viewport baxışı və CI gözləyir. |
| Mərkəzlənmiş popup | **LAV-033 hazırlanır.** İcma şikayəti və hüquqi bildiriş bütün viewport-larda həm üfüqi, həm də şaquli mərkəzdə açılır. |
| Başlıq ikonları | **LAV-034 hazırlanır.** Dil seçiminin ardınca axtarış, dəstəklənənlər və görünüş ikonları gəlir. |
| Rəy və müzakirə | **LAV-035 tamamlandı.** Etiraf altından rəy yazmaq, açıq rəyə reaksiya vermək və birbaşa cavab yazmaq mümkündür; PR #77 `main`-dədir. |
| Nömrəli səhifələmə | **LAV-036 icra olunur.** Feed, axtarış, rəy, davam, şəxsi və moderasiya siyahılarına responsiv, əlçatan nömrəli səhifələmə əlavə edilir. |

## Codex ilə başla

1. `main`-dən işə başla, [AGENTS.md](AGENTS.md), [backlog](docs/delivery/backlog.md) və [qəbul matrisi](docs/delivery/acceptance.md) fayllarını oxu.
2. [LAV-001](https://github.com/fuadoglu/LavbeEtiraf/issues/1) üzrə `BAS-01` qəbul meyarını götür. [Arxitektura](docs/architecture/system.md) shared hosting sərhədlərini müəyyən edir.
3. [Lokal yoxlamaları](#yoxlamalar) işlə. Ayrı branch-də icra et, commit və push et, issue-ya bağlı PR aç. PR-a faktiki əmr və nəticələri əlavə et.

İlk Codex tapşırığı üçün mətn:

> [LAV-001](https://github.com/fuadoglu/LavbeEtiraf/issues/1) işini icra et. Əvvəl `AGENTS.md`, arxitektura, backlog və `BAS-01` meyarını oxu. Laravel 13, Blade, nümunə mühit faylı və MySQL/MariaDB smoke testi ilə shared hostingə uyğun baza qur. GitHub Actions-da tətbiq yoxlamalarını mövcud sənəd yoxlamaları ilə birləşdir. Faktiki işlətdiyin qurulum və test əmrlərini README-yə əlavə et. Branch-i push et və issue-ya bağlı PR-da yoxlama nəticələrini göstər.

## Məhsulun quruluşu

| Sahə | Lavbe-nin davranışı |
|---|---|
| **Oxu və kəşf** | Ana lent və kateqoriyalar bir lent şablonundan qurulur. Hər HTML səhifəsində ən çox 12 etiraf olur; nömrəli səhifə keçidləri JavaScript olmadan işləyir. |
| **Anonim yazı** | 18+ istifadəçi qonaq kimi etiraf və rəy yaza bilər. Açıq ad `Lavbe #XXXXXXXX` formasındadır və yalnız həmin etiraf daxilində iştirakçını tanıdır. |
| **Öz məzmununu idarə et** | İstəyə bağlı cihaz yaddaşı, məxfi bərpa kodu və e-poçt hesabı. Etirafın açıq linki sahiblik hüququ vermir. |
| **Rəy və moderasiya** | Rəy etiraf səhifəsinin altından yazılır; təsdiqlənmiş rəylərə reaksiya və birbaşa cavab vermək mümkündür. Etiraf və rəy üçün ayrı hədəfli şikayət, ayrıca hüquqi bildiriş və sahibin bir dəfə etirazı var. Moderasiya paneli **Etiraflar · Rəylər · Şikayətlər · Etirazlar** bölmələrindən ibarətdir. |
| **Davam və bildiriş** | Müəllifin **Sonra nə oldu?** mətni eyni etirafın altında yayımlanır. Hesab sahibi etirafı izləyib tətbiqdaxili bildiriş və ayrıca seçdiyi halda neytral e-poçt ala bilər. |
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

İlk giriş `http://127.0.0.1:8000` dil seçiminə yönləndirir, məsələn `http://127.0.0.1:8000/en/`; Laravel health endpoint-i `http://127.0.0.1:8000/up` ünvanındadır. Açıq lent və detail yalnız cari locale-də canlı status, yayımlanma vaxtı və təsdiqlənmiş public revision-ı olan qeydlərdən qurulur. İctimai detail `/{locale}/confession/{public_id}`, sitemap index `/sitemap.xml` ünvanındadır. İstehsal etirafı və ilkin seed yoxdur.

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

2026-11-19: `npm ci --ignore-scripts && npm run build` keçdi; CSS bundle 15.27 KB, JavaScript 1.02 KB oldu. `composer test` 7 test/21 assertion, `composer test:mysql` 1 test/3 assertion keçdi. Mobil IAB və masaüstü Chrome-da ana görünüş, gündüz/gecə, kateqoriya və 404 yoxlandı; admin mock/modal mobil preview-da Escape ilə bağlandı və fokus açan düyməyə qayıtdı. Chrome 159-da 320 CSS px viewport-da ana və Ailə səhifəsinin `documentWidth`/`bodyWidth` ölçüləri 320px oldu; üfüqi səhifə daşması yox idi, tema dəyişməsi işlədi, kateqoriya keçidinin toxunma hündürlüyü 44px idi. Klaviaturada 8 Tab addımından sonra fokus Dostlar keçidinə çatdı və mövzu paneli onu görünən etmək üçün 38px sürüşdü. `scripts/check_wireframes_browser.py` Playwright/Chromium 159 ilə 320, 390 və 1366px enlərində 270 atlas vəziyyəti/rejim kombinasiyasını yoxladı. Admin mock yalnız `local`/`testing` mühitində açılır və sintetik məlumat göstərir. Bu nəticələr lokal baxışdır; iOS Safari, Android Chrome və standalone cihaz sınaqları LAV-012-də qalır.

## LAV-007 lokal yoxlama qeydi

2026-11-19: `composer test` 44 test/367 assertion keçdi; LAV-007 feature ssenariləri comment pending məxfiliyi və bərpası, eyni-sessiya idempotent replay, rəyin bağlanması, support-un təkrarsız aktivləşməsi və geri götürülməsi, müəllif davamı/cavabı, moderator təsdiqi, outbox idempotency və rəy gizlədiləndə cavabın gizlənməsini yoxladı. `vendor/bin/pint` keçdi. `npm run build` keçdi (CSS 17.04 KB, JS 2.47 KB). `python3 scripts/check_docs.py` 15 sənəd/27 qəbul ID-si, Python unittest 9 test keçdi. Ayrılmış lokal MariaDB `lavbe_lav007_test` bazasında təmiz migrations və `composer test:mysql` 6 test/74 assertion keçdi, o cümlədən paralel rəy bağlama/göndəriş və parent hide/comment publish yarışları. Lokalda `/up` HTTP 200; detail-də iştirakçıya görə dəyişən HTML `private, no-store`. [Documentation CI](https://github.com/fuadoglu/LavbeEtiraf/actions/runs/44495634801) və [Laravel/MySQL CI](https://github.com/fuadoglu/LavbeEtiraf/actions/runs/44495634850) keçdi.

## LAV-008 lokal yoxlama qeydi

2026-11-19: LAV-008 feature suite 4 test/49 assertion keçdi. `composer test` 48 test/416 assertion keçdi. Təmiz migrations ayrılmış MariaDB `lavbe_lav008_test` bazasında uğurla tətbiq olundu; `composer test:mysql` 7 test/85 assertion keçdi, o cümlədən eyni hesabdan paralel report göndərişi bir submission və qrup sayında bir artım yaradır. UI mətnləri az/en paketlərindədir; qalan 15 dil LAV-010-da tamamlanır. Brauzer cihaz sınağı, hüquqi operator/SLA və production server/provider aktivləşdirməsi buraxılış qapılarındadır.

## LAV-009 lokal yoxlama qeydi

2026-11-19: LAV-009 feature suite 4 test/66 assertion, `composer test` 52 test/482 assertion keçdi. Təmiz ayrılmış MariaDB `lavbe_lav009_test` bazasında migration-lar tətbiq edildi və `composer test:mysql` 8 test/97 assertion keçdi, o cümlədən paralel eyni-hesab sahiblik iddiası. `vendor/bin/pint`, docs validatoru (15 sənəd/27 qəbul ID-si), Python unittest (9), UI atlası (18 ekran/180 vəziyyət) və `npm run build` keçdi. Lokal tətbiq `127.0.0.1:8000`-də `/az/` və `/az/account/login` üçün HTTP 200 qaytarır; `/up` route-u framework-un Laravel health nümunəsini qaytardığı üçün lokal smoke nəticəsi kimi istifadə edilmir. Production e-poçt provider-i qoşulmayıb; fake transport və testlər e-poçtu yoxlayır.

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
