# Lavbe · Etiraflar Vadisi

**Lavbe telefondan anonim etiraf yazmaq, öz dilində etirafları oxumaq və hekayələrin davamını izləmək üçün mətn platformasıdır.** Məhsulun ünvanı [lavbe.com](https://lavbe.com), development reposu [LavbeEtiraf](https://github.com/fuadoglu/LavbeEtiraf)-dır.

[Codex ilə başla](#codex-ilə-başla) · [Məhsulun quruluşu](#məhsulun-quruluşu) · [Sənəd xəritəsi](#sənəd-xəritəsi) · [Yoxlamalar](#yoxlamalar) · [Buraxılış](#buraxılış)

## Cari vəziyyət

Bu bölmə 24 noyabr 2026 tarixində `main`-dəki tətbiqin vəziyyətini göstərir. İşlərin mərhələləri və tarixi sübutları [backlog](docs/delivery/backlog.md) və [qəbul matrisində](docs/delivery/acceptance.md) qalır.

| Sahə | Hazırkı vəziyyət |
|---|---|
| Oxu və yazı | Laravel 13/Blade tətbiqində 17 dil, 12 sabit kateqoriya, 12 kartlıq nömrəli lent, axtarış, açıq etiraf detalları və qonaq yazısı işləyir. Başlıq məcburidir; istəyə bağlı açıq adın yerinə hər etirafa ayrılmış, bazada unikal `Lavbe #XXXXXXXX` kodu görünür. |
| İştirak və məxfilik | Rəy forması etirafın altındadır; birbaşa cavab, reaksiya, dəstək, izləmə, paylaşma, şikayət və hüquqi bildiriş axınları var. Qonaq etirafı, rəyi və davamı baxışdan keçmədən açılmır. Şəxsi və iştirakçıya görə dəyişən detail HTML-i `private, no-store`-dur. Oxunuş sayğacı yalnız ayrıca razılıqdan sonra işləyir. |
| Hesab və heyət | E-poçt təsdiqi, şəxsi siyahılar və bərpa mövcuddur. Moderator yalnız verilmiş dillərdə, aktiv `admin/*` isə bütün 17 dildə işləyir; heyətin etirafı audit və outbox ilə birbaşa yayımlanır. Lokal MFA istisnası yalnız `APP_ENV=local` və ayrıca açıq flag ilə işləyir. |
| Görünüş və məzmun | “Gavalı mürəkkəbi” yeganə dizayndır; əvvəlki görünüş seçicisi yoxdur. Təqdim olunan SVG logo, kateqoriya ikon/favikonları, responsiv naviqasiya və altı oxucu hüquqi/məlumat səhifəsi 17 dildə mövcuddur. Operator səhifəsi ayrıca URL ilə əlçatandır, footer-də keçidi yoxdur. Məhsul sahibi 17 dil üzrə baxışı 22 noyabr 2026-da təsdiqləyib. |
| PWA və xarici xidmətlər | Statik oflayn qabıq və manifest tətbiq olunub; PWA etiraf HTML-ini, şəxsi səhifələri və qaralamaları keşə yazmır. GA4 və AdSense üçün admin idarəsi var, lakin xarici xidmətlər başlanğıcda və deploy hazır olma qapısında bağlıdır. Web Push və real cihaz quraşdırması ayrıca yoxlamadır. |
| İctimai buraxılış | Hosting, hüquqi operator məlumatları, real cihazlar, backup/bərpa, yük və xarici xidmətlərin faktiki sınaqları [buraxılış qapıları](docs/delivery/operations.md) üzrə açıqdır. Açıq [GitHub işləri](https://github.com/fuadoglu/LavbeEtiraf/issues) bu yoxlamaları izləyir; lokal və CI nəticələri ictimai yayıma avtomatik icazə vermir. |

## Codex ilə başla

1. `main`-dən ayrıca branch açmazdan əvvəl [AGENTS.md](AGENTS.md), bu README-ni, [məhsul qərarlarını](docs/product.md), [backlog-u](docs/delivery/backlog.md) və [qəbul matrisini](docs/delivery/acceptance.md) oxu.
2. Bir aydın iş həcmi və qəbul ID-ləri seç. Köhnə LAV bölmələri həmin vaxtın tətbiq mərhələsini təsvir edə bilər; son qərar və cari davranış üçün məhsul sənədini, daha yeni LAV işini, qəbul meyarını və işlək kodu tutuşdur.
3. Aidiyyəti yoxlamaları işlə; nəticələri və açıq qalan real cihaz/xarici mühit yoxlamalarını issue-ya bağlı PR-da ayrıca göstər. İctimai yayım üçün [əməliyyat qapıları](docs/delivery/operations.md) tətbiq olunur.

## Məhsulun quruluşu

| Sahə | Lavbe-nin davranışı |
|---|---|
| **Oxu və kəşf** | Ana lent və kateqoriyalar bir lent şablonundan qurulur. Hər HTML səhifəsində ən çox 12 etiraf olur; nömrəli səhifə keçidləri JavaScript olmadan işləyir. İctimai etiraf səhifəsində unikal oxunuş sayı görünür. |
| **Anonim yazı** | 18+ istifadəçi qonaq kimi etiraf və rəy yaza bilər. İstəyə bağlı açıq ad göstərilmirsə, `Lavbe #XXXXXXXX` kodu hər etiraf üçün ayrıdır və bütün etiraflar üzrə unikaldır. |
| **Öz məzmununu idarə et** | İstəyə bağlı cihaz yaddaşı, məxfi bərpa kodu və e-poçt hesabı. Qonaq “Mənim” bölməsində həmin cihazda yadda saxlanmış etiraflarının başlığını və moderasiya vəziyyətini görüb idarə edə bilər. Etirafın açıq linki sahiblik hüququ vermir. |
| **Rəy və moderasiya** | Rəy etiraf səhifəsinin altından yazılır; təsdiqlənmiş rəylərə reaksiya və birbaşa cavab vermək mümkündür. Etiraf və rəy üçün ayrı hədəfli şikayət, ayrıca hüquqi bildiriş və sahibin bir dəfə etirazı var. Moderasiya paneli **Etiraflar · Rəylər · Şikayətlər · Etirazlar** bölmələrindən ibarətdir. |
| **Davam və bildiriş** | Müəllifin **Sonra nə oldu?** mətni eyni etirafın altında yayımlanır. Hesab sahibi etirafı izləyib tətbiqdaxili bildiriş və ayrıca seçdiyi halda neytral e-poçt ala bilər. |
| **Dil və görünüş** | 17 dil, 12 kateqoriya, axtarışlı dil menyusu, yumru bayraqlar, dilin öz adı və ingiliscə adı. Header-də desktop üçün bayraqla tam native ad, mobil üçün yalnız bayraq göstərilir; locale kodu istifadəçiyə çıxmır. Saxlanmış seçim cihaz və ölkə siqnalından üstündür. Seçilmiş lent yalnız həmin dildədir. |
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

İlk giriş `http://127.0.0.1:8000` dil seçiminə yönləndirir, məsələn `http://127.0.0.1:8000/en/`; Laravel health endpoint-i `http://127.0.0.1:8000/up` ünvanındadır. Açıq lent və detail yalnız cari locale-də canlı status, yayımlanma vaxtı və təsdiqlənmiş public revision-ı olan qeydlərdən qurulur. İctimai detail `/{locale}/confession/{public_id}`, sitemap index `/sitemap.xml` ünvanındadır. İstehsal etirafı və production seed yoxdur. Lokal baxış üçün `php artisan db:seed --class=AzerbaijaniDemoConfessionSeeder` yalnız local mühitdə iki uydurma etiraf/kateqoriya yaradır.

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

Tam ictimai buraxılış üçün real shared hosting, məlumatın yeri və operator kimliyi, 17 dil üzrə moderator təminatı, SEO və razılıq sınaqları, backup və bərpa üzrə sübut tələb olunur. 17 dil üzrə mətn baxışı tamamlanıb; faktiki hüquqi məlumatlar ayrıca təsdiqlənməlidir. AdSense aktivləşdirməsi üçün domen, `ads.txt` və razılıq yoxlaması ayrıca tamamlanır. [Buraxılış və geri dönüş qaydaları](docs/delivery/operations.md)

## LAV-001 lokal yoxlama qeydi

2026-11-19 macOS development mühitində PHP 8.4.10, Composer 2.8.10 və MariaDB 12.1.2 ilə işlədildi. Təmiz Laravel install-dan Composer lock `laravel/framework` v13.43.0 versiyasını seçdi. `php artisan migrate --force` keçdi; MySQL smoke testi 1 test/3 assertion, Laravel suite 3 test/7 assertion, `/up` sorğusu HTTP 200 qaytardı. `python3 scripts/check_docs.py` 15 sənəd və 27 qəbul ID-sini yoxladı, unittest 7 test keçirdi, atlas yoxlaması 18 ekran/180 UI kombinasiyasını yoxladı. Təmiz GitHub Actions [Application run](https://github.com/fuadoglu/LavbeEtiraf/actions/runs/44426405266) MySQL 8.4 migration, Laravel suite və smoke testini, [Documentation run](https://github.com/fuadoglu/LavbeEtiraf/actions/runs/44426405252) isə sənəd workflow-unu keçirdi.
