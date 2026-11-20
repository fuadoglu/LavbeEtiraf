# Hardcode auditi

Bu sənəd tətbiq kodundakı sabit dəyərləri kateqoriyalara ayırır. Məqsəd hər literal-ı config-ə köçürmək deyil: dəyişməsi məhsul və ya mühit qərarı olan dəyərin bir sahibi olmalı, standart və təhlükəsizlik kontraktları isə sabit qalmalıdır.

## LAV-016 çərçivəsində mərkəzləşdirilənlər

- Mətn limitləri, sorğu ölçüsü, recovery/idempotency uzunluqları: `config/lavbe.php` → `limits`.
- Hesab və moderasiya siyahılarının ölçüləri: `pagination`. Lent ölçüsü və cursor TTL-i config/feed.php mənbəyindədir.
- Cookie müddətləri, cursor TTL, rate-limit cəhdləri və pəncərələri: `cookies`, `config/feed.php → cursor_ttl_minutes`, `rate_limits`.
- Feed sıralama adları dil fayllarından alınır; config yalnız tərcümə açarını saxlayır.
- UUID v4 route pattern-i və report target/channel siyahıları `RoutePatterns` sinfində bir dəfə saxlanır.
- Formanın maxlength/minlength dəyərləri server validasiyası ilə eyni config-i istifadə edir.

## Qəsdən literal qalanlar

| Qrup | Nümunə | Səbəb |
|---|---|---|
| Protokol və serializasiya | HTTP status/başlıqları, `UTF-8`, XML deklarasiyası, RFC/ISO regex-ləri | Bunlar deploy-a görə dəyişən məhsul parametri deyil; dəyişmək inteqrasiya kontraktını pozar. |
| Kriptoqrafik format | SHA-256, AES-GCM nonce/tag ölçüsü, TOTP formatı | Alqoritm və byte uzunluqları təhlükəsizlik/protokol kontraktıdır; config dəyişəni kimi açılmamalıdır. |
| DB sxemi və enum | status, qərar, kateqoriya açarları, migration sütun ölçüsü | Sxem və saxlanmış tarixi dəyərlərlə uyğunluq tələb edir. Dəyişiklik ayrıca migration/domain işi olmalıdır. |
| Test və nümunə | Factory/fixture dəyərləri, docs nümunələri, assert olunan standartlar | Məhsul runtime konfiqurasiyasını təmsil etmir; test semantikasını oxunaqlı saxlayır. |
| CSS/design token | Rəng, aralıq, radius və kölgə | Bir dizayn sistemi səviyyəsində tokenləşmə işi tələb edir; LAV-016 runtime config-ə daşıma etmir. |

## Davam edən audit siyahısı

- CSS tokenlərinin `:root` və dark theme üzrə tək mənbədə saxlanmasını yoxla; mövcud custom property-lərin həqiqətən təkrarlandığı halları sonrakı UI işi kimi düzəlt.
- Provider və mühitə bağlı host, timeout, retry sayı, feature toggle və xarici ID-ləri mövcud `config/services.php`, `config/*.php` və `.env.example` ilə tutuşdur. Secret və production ID-ni source code-a yazma.
- Domain status/decision string-lərinin təkrarını yalnız invariantı qoruyan typed enum/value object ilə dəyişmək mümkündürsə planlaşdır; DB kontraktını config-ə çevirmə.
- Şablon və JavaScript-dəki literal mətni locale fayllarına köçür; standart aria/HTML dəyərlərini isə tərcümə etmə.
- Hər yeni auditdə literal-ın sahibi, dəyişmə səbəbi və doğrulama testini qeyd et.

## Yoxlama

CFG-01 üçün testlər ən azı bir config limitinin dəyişdirilməsi ilə həm server yoxlamasının, həm də HTML sahə limitinin dəyişdiyini sübut edir. Yeni runtime tuning dəyəri əlavə olunanda default davranış əvvəlki ilə eyni qalmalıdır.
