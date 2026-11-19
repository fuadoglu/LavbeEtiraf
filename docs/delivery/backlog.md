# Codex icra backlog-u

GitHub Issues icra statusunun mənbəyidir. Bu sənəd iş həcmini, məsuliyyəti, asılılığı və qəbul referensini saxlayır. Məhsul sahibi istiqaməti və dil baxışını təsdiqləyir. Codex hər işi ayrıca branch-də icra edir, test edir, push edir və PR açır. Sprintin nəticəsi işlək daxili artımdır. İctimai buraxılış [qapılar](operations.md) keçəndə edilir.

| Sprint | Məqsəd | İşlər |
|---|---|---|
| Baza | İcra sənədləri və doğrulama | LAV-000 |
| 1 | Serverdən lokal lent və UI | LAV-001, LAV-002, LAV-003 |
| 2 | Qonaq etirafından açıq yayıma | LAV-004, LAV-005, LAV-006 |
| 3 | Rəy, şikayət, davam və şəxsi idarəetmə | LAV-007, LAV-008, LAV-009 |
| 4 | 17 dil, cihaz sınaqları və PWA | LAV-010, LAV-011, LAV-012 |
| 5 | Ölçüm, xarici axtarış və tam buraxılış | LAV-013, LAV-014, LAV-015 |

## LAV-000 · İcra spesifikasiyası

**Asılılıq:** baza commit. **Qəbul:** DOC-01.

Sahiblik/münaqişə, lent, reklam/razılıq, 17 dil və 12 kateqoriya reyestri, wireframe və backlog xəritəsini hazırlanmış repoya əlavə et. PR-da düzgün və mutasiya edilmiş nümunələr üzrə validator nəticələrini göstər.

## LAV-001 · Tətbiq əsası

**Asılılıq:** baza commit. Sənəd PR-ı ilə paralel başlaya bilər. **Qəbul:** BAS-01.

Laravel 13, minimum PHP 8.4, Blade, nümunə mühit faylı, Composer lock və MySQL ailəsi ilə smoke test. `public/` entry point, health və lokaldan quraşdırma əmrləri. CI sənəd və tətbiq sınağını işlədir. **Sübut:** təmiz checkout logu və CI yoxlaması.

## LAV-002 · Dizayn sistemi və ekran komponentləri

**Asılılıq:** LAV-000, LAV-001. **Qəbul:** UI-01, UI-04.

Ortaq Blade başlığı, naviqasiya, forma, status, kart, mövzu paneli, skeleton, modal və sistem ekranları. Gündüz/gecə, fokus, azaldılmış hərəkət və yerli Lucide aktivləri. **Sübut:** wireframe-a uyğun mobil/masaüstü müqayisə və klaviatura sınağı.

## LAV-003 · Dil reyestri, ana səhifə və lent

**Asılılıq:** LAV-000, LAV-001, LAV-002. **Qəbul:** UI-02, LOC-01.

Dil seçiminin prioriteti, axtarışlı menyu, kateqoriya və üç lent rejimi, ilkin HTML, 12 kart, adi link və cursor. Daxili axtarış eyni dilə məhdud qalır. **İcra:** `/{locale}/` və `/{locale}/topics/{key}` HTML, 17 UI locale faylı, public-only read model, səhifələmə, HMAC şifrəli 60 dəqiqəlik feed cursor-u və POST axtarış. Fixture yalnız test factory-dən gəlir. **Sübut:** feature/unit sınaqlar; snapshot-a bağlı həftəlik/yeniləmə sırası, visibility revision və real qonaq/moderasiya yazı modeli LAV-004/005-dən sonra tamamlanacaq, bu mərhələdə tətbiq iddiası yoxdur.

## LAV-004 · Qonaq etirafı və sahiblik

**Asılılıq:** LAV-000, LAV-001, LAV-002, LAV-003. **Qəbul:** OWN-01, OWN-03.

Mətn forması, seçilən dil və mövzu, yaş bildirişi, idempotent göndəriş, alias, sessiya/qonaq açarı və rotasiya olunan bərpa. **Sübut:** paralel bərpa və cross-owner imtina, açar və mətnin ictimai cavaba düşməməsi.

İlkin göndəriş `unpublished` etiraf və `pending` dəyişməz mətn versiyası yaradır; başlıq istəyə bağlı 120, mətn 6 000 Unicode code point ilə məhdudlaşır. İctimai feed/read model boş və dərcsiz qalır. İctimai ID, etirafdaxili `Lavbe #XXXXXXXX`, cari Laravel session ID-si, istəyə bağlı cihaz cookie-si və bərpa sirri ayrı dəyərlərdir. Session ID HMAC-SHA-256 ilə sahiblik cədvəlində bağlanır; 256-bit recovery və seçilərsə owner cookie dəyərləri yalnız HMAC-SHA-256 heşi ilə saxlanır. Uzunömürlü HttpOnly/Secure/SameSite cookie yalnız cihazı yadda saxlamaq seçiləndə verilir. Idempotency replay eyni nəticəyə yönəlir, amma sirri ikinci dəfə qaytarmır; fərqli payload `409` alır. POST recovery tranzaksiyada etirafı və sahiblik sətrini kilidləyir, sessiya ID-sini və bərpa dəyərlərini rotasiya edib köhnə icazələri ləğv edir. Şəxsi səthlər `private, no-store`, noindex və no-referrer qaytarır. Saxlama xətası yalnız SQLSTATE loglayır, mətn və sirləri loglamır. **LAV-004 sərhədi:** moderator keçidi və yayımlama yoxdur.

## LAV-005 · Moderasiya və dərc keçidləri

**Asılılıq:** LAV-004. **Qəbul:** MOD-01, MOD-04.

Moderator identifikasiyası `users` hesabına bağlı olur, amma adi hesab özü-özünə moderator deyil. Moderator grant-ı aktivləşdirilmiş və auditlənmiş roldur; etirafın `locale` dəyərinə görə ayrıca dil grant-ı tələb olunur. Admin rolu dil grant-ını avtomatik vermir. MFA təsdiqi sessiyaya bağlı server-side timestamp/assurance kimi saxlanır, TOTP sirri şifrəli saxlanır; moderator panelinə və hər qərara yaxın zamanda təzə MFA yoxlaması lazımdır. Dil/rol grant-ı dəyişməsi üçün aktiv admin, təzə MFA və səbəb tələb olunur.

Qərar POST-u `target_id`, `target_type`, `revision_id`, `expected_lock_version`, `decision`, səbəb və təsadüfi idempotency key daşıyır. Server grant, MFA, cari active revision, həmin revision-ın qərara açıq vəziyyətini və lock version-u eyni tranzaksiyada yenidən yoxlayır; köhnə tab `409` alır. Moderator baxışında cari pending mətn, əvvəlki public revision (varsa), locale/kateqoriya və siyasət konteksti göstərilir; qonaq session/cookie/recovery dəyərləri, xam IP və e-poçt göstərilmir. Düzəliş istə/rədd üçün müəllifə göstərilən səbəb tələb olunur; yalnız daxili qeyd müəllif cavabına düşmür.

Audit append-only qərar qeydi kimi aktor ID-si, grant snapshot-ı, target/revision, əvvəlki/yeni status, decision, sanitizasiya olunmuş səbəb, request ID, lock version, zaman və əvvəlki/yeni public revision ID/hash saxlayır. Mətn, token, session, recovery sirri, IP və e-poçt audit payload-a daxil edilmir. İctimai indeks və ya cache üçün yan təsirlər outbox-dadır. Revision statusu, active review, public pointer, görünürlük sayğacı, audit və uyğun outbox event eyni MySQL transaction-da yazılır. Xarici xidmət çağırışı transaction daxilində edilmir; outbox consumer hadisə generation-ını yoxlayır ki, köhnə event yeni görünürlüyü geri çevirməsin.

MVP qərarları: `approve`, `request_changes`, `reject`, `hide`, `restore` və `terminal_delete` ayrı əmrlərdir. `approve` yalnız cari pending revision-a açıq pointer verir; `request_changes`/`reject` əvvəlki public pointer-ı saxlayır; `hide` public pointer-ı oxudan çıxarır və `visibility_version` artırır; `restore` yalnız əvvəl təsdiqlənmiş revision-ı ayrıca cari məzmun/reklam baxışından sonra göstərə bilər; `terminal_delete` terminal status verir və açıq məzmunu silir, mətnsiz audit markerini saxlayır. Müəllif `withdrawn` vəziyyətinə keçirəndə köhnə approval əmri etibarsız qalır. Bir moderator öz grant-ını yarada və ya başqa istifadəçinin MFA qurğusunu dəyişə bilməz.

**Sübut:** OWN-01 pending-only invariantı qorunur; MySQL integration testlərdə language grant mismatch=404/403 policy, MFA yoxdursa/vaxtı keçibsə 403, köhnə revision/lock version=409, qərar idempotency replay, audit redaksiyası, approve/revoke-outbox transaction atomikliyi, moderator icazə dəyişikliyi audit-i, və geri çəkmə–approve, hide–approve, double-approve yarışlarının nəticəsi yoxlanır. Hər yarışda yalnız bir cari expected-version əmri uğur qazanır, stale qərar public məzmunu bərpa etmir. İstehsal moderator hesabı və TOTP secret seed edilmir; ilkin hesablar ayrıca səlahiyyətli əməliyyat addımıdır.

## LAV-006 · Açıq etiraf və texniki SEO

**Asılılıq:** LAV-003, LAV-005. **Qəbul:** SEO-01, SEO-02, SEO-04.

Etiraf HTML-si, sabit canonical, status, həqiqi lastmod, locale sitemap, kateqoriya indeksi və uyğun hreflang. **Sübut:** aktiv, redaktə edilmiş, geri çəkilmiş və silinmiş URL-lərin HTML/XML auditini eyni fixture-lə işlə.

**İcra:** `/{locale}/confession/{public_id}` yalnız approved cari public revision-ı render edir; public olmayan vəziyyət 404, əvvəl public olmuş terminal silinmə 410-dur. `public_updated_at` yalnız təsdiqlənmiş məzmun yenilənəndə dəyişir, status keçidi bunu saxtalaşdırmır. `/sitemap.xml` locale üzrə bölünən sitemap URL-lərini göstərir; locale sitemap-ləri home, beş canlı materialı olan kateqoriyalar və canlı confession URL-lərini ehtiva edir. Sitemap və detail `no-cache, must-revalidate` qaytarır ki, gizlətmə/silmə cavabı origin-də yenidən yoxlanılsın. Hreflang yalnız semantik baxımdan eyni home/kateqoriya səhifələri üçündür; müstəqil confession-lar tərcümə kimi işarələnmir.

## LAV-007 · Rəylər, davam və dəstək

**Asılılıq:** LAV-004, LAV-005, LAV-006. **Qəbul:** MOD-03, OWN-03.

Ayrı rəy formu, bir səviyyə, müəllif cavabı, eyni hekayədə davam, dəstək və rəy seçimi. Yalnız təsdiq edilmiş canlı uşaq materialı göstər. **Sübut:** rəy bağlama, rəy gizlətmə, paralel valideyn gizlətməsi və bildiriş təkrarı sınaqları.

**Tətbiq kontraktı:** Rəy və müəllif cavabı mətnləri dəyişməz revision-larda gözləyir; yalnız moderatorun təsdiqlədiyi cari revision ictimai olur. Rəy sahibi anonim iştirakçı alias-ı, sessiya, seçilən cihaz cookie-si və rotasiya olunan birdəfəlik bərpa kodu ilə idarə edir. Bərpa sirri yalnız HMAC heşi ilə saxlanır. Müəllifin rəyə bir cavab yeri var; düzəliş yeni pending revision yaradır. Davam yalnız etiraf sahibinə açıqdır. Rəyin açıq/bağlı seçimi yeni göndərişi bloklayır, əvvəl təsdiqlənmiş rəyləri saxlayır. Dəstək etiraf-sessiya iştirakçısı üzrə unikal aktiv reaksiyadır; say parent kilidi altında dəyişir. Moderasiya qərarı parent lock, target/revision lock, audit və outbox-u eyni tranzaksiyada yazır. Uşaq təsdiqi/gizlətməsi parent visibility version-ını artırır; parent gizlədilməsi bütün uşaq sorğularını public scope-dan çıxarır. Rəy bildirişləri 15 dəqiqəlik outbox qrup açarı ilə birləşir, davam başına bir hadisə yaranır. Outbox-da mətn yoxdur, çatdırılma LAV-009-a aiddir. İctimai detail-in iştirakçıya görə dəyişən HTML-i `private, no-store` olur.

**Hazırkı sübut:** SQLite feature suite comment privacy/recovery, rəy bağlama, support idempotency və geri götürmə, continuation, author response və comment-hide cascade-ni yoxlayır. MariaDB lokal ayrılmış test bazasında bütün migration-lar və MySQL qrupundakı 6 test/74 assertion, o cümlədən close-review/submit və parent-hide/comment-publish prosesslərarası yarışları keçir.

## LAV-008 · Şikayət, təcili baxış və etiraz

**Asılılıq:** LAV-005, LAV-007. **Qəbul:** MOD-02, MOD-04.

Etiraf/rəy üçün fərqli şikayət hədəfi, hədəf+kanal üzrə birləşən şikayət qrupu, reporter üzrə tək göndəriş, səbəblər, təhdid üçün server hesablı təcili prioritet və ayrıca hüquqi bildiriş növbəsi. Kütləvi şikayət məzmunu avtomatik gizlətmir. Moderator dili/MFA yoxlaması ilə şikayət kontekstini görür və səbəbli dismiss/hide qərarı verir; audit və görünürlük eyni tranzaksiyadadır. Gizlədilən etiraf və rəy sahibi həmin hide auditinə yalnız bir dəfə etiraz edə, moderator isə etirazı uphold/restore edə bilər; uyğun başqa təsdiqlənmiş moderator olduqda ilkin qərar verən şəxs yenidən baxmır. İzahlar şifrələnir, reporter identity HMAC olur, xam IP/session/token auditə düşmür. Hüquqi səbəblər ayrıca növbəyə düşür; hüquqi operator/SLA faktı burada təsdiqlənmir. **Sübut:** SQLite feature suite 4 test/49 assertion ilə hədəf və kanal ayrımını, hesab üzrə deduplikanı, təhdid prioritetini, şifrəli izahı, məzmunun dəyişməməsini, təzə MFA/dil grant-ını, dismiss/hide auditini və sahibə bağlı tək etiraz/bərpanı yoxlayır. Ayrılmış MariaDB-də təmiz migration və `composer test:mysql` 7 test/85 assertion keçdi; prosesslərarası eyni hesab şikayəti bir göndəriş/group count yaradır.

## LAV-009 · Hesab, Mənim və bildirişlər

**Asılılıq:** LAV-004, LAV-005, LAV-007. **Qəbul:** OWN-02, ADV-03.

Təsdiqli e-poçt qeydiyyatı, giriş/parol bərpası, şəxsi Mənim bölməsi, sübutla qonaq etiraf və rəy sahiblik transferi, account-only izləmə, tətbiqdaxili seçimlər və e-poçt üçün ayrıca opt-in. Mövcud participation outbox qısa scheduler komandası ilə idempotent hesab bildirişinə çevrilir; mail mətnində yalnız ümumi fəaliyyət bildirişi və şəxsi bölməyə keçid var. **Sübut:** e-poçt təsdiqi və hesablararası izolyasiya, exact-target sahiblik, duplicate outbox, opt-in mail payload məxfiliyi, şəxsi cavab `no-store`; MySQL-də paralel eyni-hesaba transfer.

**Yerli yoxlama:** `composer test` 52 test/482 assertion; təmiz MariaDB `lavbe_lav009_test` migration və `composer test:mysql` 8 test/97 assertion; Pint, docs validatoru, 9 Python test, 18 ekran/180 UI atlas vəziyyəti və frontend build keçdi. Production mail relay qoşulu deyil. Merge-dən sonra GitHub PR checks qalır.

## LAV-010 · 17 dil paketi və sahib baxışı

**Asılılıq:** LAV-003, LAV-008, LAV-009. **Qəbul:** LOC-02, LOC-03.

Codex hər dil paketi üçün mətnləri, kateqoriyaları və hüquqi draftları hazırlayır. Variantlar ekrana görə ixrac edilir və məhsul sahibi dəyişikliklərə baxır. **Sübut:** paket validatoru, düzəliş diff-i və qeydə alınmış baxış. Xarici hüquqi faktlar draftı keçmədən açılmır.

**İcra qeydi:** 169 açarlı kanonik ingilis mənbə `spec/locale-messages.en.json` faylındadır; 17 ilkin maşın tərcüməsi draftı və paketi, source/package validatoru, draft-dan paket qurucusu və `pending` review CSV/JSON ixracı yaradılıb. Chromium preview-u bütün locale mətnlərini 320/390/1366px enlərində göstərib, 51 yoxlamada horizontal overflow tapmayıb. Məhsul sahibinin yekun mətn baxışı və real cihaz yoxlaması LAV-010-un qalan işidir. LAV-011 üçün əlavə olunan `draft_saved` və `draft_storage_error` mətnləri də eyni paketlərdə draft olaraq saxlanılır və sahib baxışı gözləyir.

## LAV-011 · PWA, oflayn və opt-in push

**Asılılıq:** LAV-002, LAV-004, LAV-009, LAV-010. **Qəbul:** PWA-01, ADV-03.

Manifest, ikon, standalone, versiyalı statik service worker keşi, tərcüməli oflayn görünüş, istifadəçi razılığı ilə 24 saatlıq cihaz qaralaması və account-scoped Web Push. **Sübut:** feature/MySQL testləri, frontend/documentation CI və cache/privacy yoxlaması. **Qalan qəbul:** owner locale copy review-u, iOS Safari/Android Chrome/standalone real cihaz sınağı və VAPID/provider konfiqurasiyalı staging push yoxlaması.

## LAV-012 · Cihaz, UI və dil sınaqları

**Asılılıq:** LAV-006, LAV-007, LAV-008, LAV-010, LAV-011. **Qəbul:** UI-01, UI-03, UI-04, LOC-03.

Kiçik mobil, iOS Safari, Android Chrome, masaüstü və standalone. Gündüz/gecə, klaviatura, geri düyməsi, dəstək və şikayətlər. **Sübut:** viewport və dil bazası, ekran görüntüləri, cihaz/OS versiyası və faktiki nəticə.

## LAV-013 · GA4, CMP və AdSense

**Asılılıq:** LAV-006, LAV-007, LAV-009, LAV-010. **Qəbul:** ADV-01, ADV-02, ADV-03.

İlkin icra: razılıq paneli, Basic Consent Mode üçün razılıqdan sonra GA4, ictimai səth whitelist-i və ads default-off. Reklam slotu/ads.txt təsdiqlənmiş CMP, publisher ID və məzmun uyğunluğu yoxlaması ilə ayrıca tamamlanmalıdır. **Sübut:** sintetik network trace-də əvvəl-sonra-geri alma, uyğun olmayan rəy və ayrıca rəy formu. İstehsal ID-ləri səlahiyyətli hesabla qoşulur.

## LAV-014 · Xarici axtarış yoxlaması

**Asılılıq:** LAV-006, LAV-008. **Qəbul:** SEO-03, SEO-04.

Search Console, Bing, IndexNow outbox, iş nəticəsi və retry, sitemap və status auditi. **Sübut:** testdə provider contract və retry. İstehsalda təsdiqlənmiş domen, sitemap və canlı bildiriş qeydi. İndeks mövqeyi xarici nəticədir.

## LAV-015 · Shared hosting buraxılış sübutu

**Asılılıq:** LAV-010, LAV-011, LAV-012, LAV-013, LAV-014. **Qəbul:** OPS-01, OPS-02, OPS-03.

Real hosting, cron, SMTP, rollback, məlumatın geri gəlməməsi, 17 dilin istismar təminatı və istifadəçi axınları. Əvvəl staging, sonra yayım səlahiyyəti. **Sübut:** runbook, rollback və real yükləmə nəticəsi. Hesab və hüquqi təsdiqlər [əməliyyat qapılarına](operations.md) uyğun bağlanır.

## İş dövrü

Hər issue məqsədi, asılılığı, qəbul ID-si və sübutu daşıyır. Branch → commit → push → draft/hazır PR → CI → sahib baxışı. Sprint sonunda nəticə, blok, cavabdeh və növbəti prioritet yazılır. CI adı sabitləşəndən sonra main üçün yoxlama qaydası tətbiq edilir. GitHub statusunu sprint sənədində təkrarlama. Sənədlər push olunanda yeni Codex icrası avtomatik başlanmır.
