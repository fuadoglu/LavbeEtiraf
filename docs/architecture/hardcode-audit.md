# Hardcode auditi

Bu sənəd tətbiq kodundakı sabit dəyərləri kateqoriyalara ayırır. Məqsəd hər literal-ı config-ə köçürmək deyil: dəyişməsi məhsul və ya mühit qərarı olan dəyərin bir sahibi olmalı, standart və təhlükəsizlik kontraktları isə sabit qalmalıdır.

## Mərkəzi mənbələri

- Mətn limitləri, sorğu ölçüsü, recovery/idempotency uzunluqları: `config/lavbe.php` → `limits`.
- Rəy, davam, dəstəklənənlər, hesab, moderasiya və admin axtarış siyahılarının ölçüləri: `config/lavbe.php` → `pagination`. Lent ölçüsü `config/feed.php`-də qalır; cursor TTL-i də həmin config mənbəyindədir.
- Thread-də kök rəylərin hər səhifədəki sayı, cavab səhifəsinin ölçüsü və ilk səhifədəki cavab önbaxışı ayrı pagination dəyərləridir. Səhifə query açarları view/controller kontraktıdır; hər list üçün unikaldır.
- Cookie müddətləri, cursor TTL, rate-limit cəhdləri və pəncərələri: `cookies`, `config/feed.php → cursor_ttl_minutes`, `rate_limits`.
- Feed sıralama adları dil fayllarından alınır; config yalnız tərcümə açarını saxlayır.
- UUID v4 route pattern-i və report target/channel siyahıları `RoutePatterns` sinfində bir dəfə saxlanır.
- Formanın maxlength/minlength dəyərləri server validasiyası ilə eyni config-i istifadə edir.
- Transaction retry sayı `config/database.php`-dədir və `DB_TRANSACTION_RETRIES` ilə mühitə görə dəyişir.
- IndexNow endpoint və cəhd sayı `config/services.php`-dədir; dəyərlər `.env.example`-də sənədləşir.
- Theme palitrası `config/theme.php`-dədir. CSS komponentləri həmin dəyərləri custom property-lərdən oxuyur.
- Offline ekranının başlıq/mətn/düymə tərcümələri 17 `resources/lang/*/lavbe.php` paketindədir. `scripts/build_offline_copy.php` JSON artefaktını yaradır; Composer autoload mərhələsi bunu yeniləyir, service worker da offline üçün öncədən keşləyir.
- Tətbiq adı başlıq, manifest və müəllif aliasında `APP_NAME`/`config('app.name')`-dən gəlir. Hüquqi mətnlərdə sayt adı, domen və oxunuş məlumatının saxlanma müddəti locale faylında parametrdir və route-da konfiqurasiyadan doldurulur.
- Idempotency key formatı `IdempotencyKey` sinfindədir; müəllif aliasının formatı `AuthorAlias`-da mərkəzləşir.

## Qəsdən literal qalanlar

| Qrup | Nümunə | Səbəb |
|---|---|---|
| Protokol və serializasiya | HTTP status/başlıqları, `UTF-8`, XML deklarasiyası, RFC/ISO regex-ləri | Bunlar deploy-a görə dəyişən məhsul parametri deyil; dəyişmək inteqrasiya kontraktını pozar. |
| Kriptoqrafik format | SHA-256, AES-GCM nonce/tag ölçüsü, TOTP formatı | Alqoritm və byte uzunluqları təhlükəsizlik/protokol kontraktıdır; config dəyişəni kimi açılmamalıdır. |
| DB sxemi və enum | status, qərar, kateqoriya açarları, migration sütun ölçüsü | Sxem və saxlanmış tarixi dəyərlərlə uyğunluq tələb edir. Dəyişiklik ayrıca migration/domain işi olmalıdır. |
| Test və nümunə | Factory/fixture dəyərləri, docs nümunələri, assert olunan standartlar | Məhsul runtime konfiqurasiyasını təmsil etmir; test semantikasını oxunaqlı saxlayır. |
| CSS layout dəyərləri | `rem`, `px`, `clamp()` və breakpoint ölçüləri | CSS layout qaydaları stylesheet-də komponentin təqdimat kontraktıdır; bunlar server/məhsul konfiqurasiyası deyil. Rəng palitrası isə `config/theme.php`-dədir. |

## Qalan literal-ların yoxlanma qaydası

- Literal axtarışının hər nəticəsini yuxarıdakı sahiblərdən birinə bağla. Məhsul/mühit dəyəri config-ə, istifadəçi mətni locale paketinə, təkrarlanan regex/API formatı isə named contract-a keçir.
- Domain status/decision string-lərini yalnız invariantı qoruyan typed enum/value object ilə dəyiş; DB kontraktını config-ə çevirmə.
- HTTP status kodları, MIME type, RFC/ISO formatları və kriptoqrafik byte ölçüləri yalnız rəsmi kontrakt dəyişəndə dəyişir; tuning env-i kimi açılmır.
- Test fixture və sənəd nümunələrindəki sabitlər runtime parametr sayılmır.
- Yeni literal əlavə olunanda onun sahibi və səbəbi eyni dəyişiklikdə göstərilir. Dəyişə bilən runtime parametrin tətbiq kodunda təkrar default-u buraxılmır.

## Yoxlama

CFG-01 üçün CI qəbul yoxlamaları ən azı bir config limitinin dəyişdirilməsi ilə həm server yoxlamasının, həm də HTML sahə limitinin dəyişdiyini sübut etməlidir. Yeni runtime tuning dəyəri əlavə olunanda default davranış əvvəlki ilə eyni qalmalıdır. Offline tərcümə artefaktı üçün generatorun bütün 17 canonical locale-i çıxardığını yoxlamaq lazımdır.
