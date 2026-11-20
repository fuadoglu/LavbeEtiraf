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
| 6 | Layihə sabit dəyərlərinin auditi | LAV-016 |
| 7 | Oxucu kəşfi və açıq müəllif adı | LAV-030 |
| 8 | Etiraf bildiriş formalarının modal təqdimatı | LAV-031 |
| 9 | Modal popup-ların mobil/desktop uyğunlaşması | LAV-032 |
| 10 | Popup-ların bütün ekranlarda mərkəzlənməsi | LAV-033 |
| 11 | Başlıq ikonlarının dil menyusundan sonra düzülməsi | LAV-034 |
| 12 | Etiraf altında thread-li rəy və reaksiya | LAV-035 |
| 13 | Bütün artan siyahıların nömrəli səhifələnməsi | LAV-036 |
| 14 | Davamı gələnlər lentinin Məşhur etiraflarla əvəzlənməsi | LAV-037 |
| 15 | Seçilmiş dilin desktop və mobil header təqdimatı | LAV-038 |
| 16 | Azərbaycan nümunələri və Lavbe wordmark loqosu | LAV-039 |
| 17 | 17 dildə yeni mövzu taksonomiyası | LAV-040 |
| 18 | Təqdim olunan Lavbe logosu və lələk favicon-u | LAV-041 |
| 19 | 12 kateqoriya üçün unikal və mövzuya uyğun ikonlar | LAV-042 |
| 20 | Etiraf oxunuşlarını brauzer profilinə görə unikallaşdır və kateqoriya favicon-u göstər | LAV-043 |
| 21 | Unikal oxunuş sayını etiraf kartının alt sağında göstər | LAV-044 |
| 22 | Tövsiyə olunan etirafları müxtəlif kateqoriyalardan göstər | LAV-045 |
| 23 | Footer-i əlçatan və responsive standartlara uyğunlaşdır | LAV-046 |
| 24 | Footer-də ürəkli Lavbe loqosu və sağda yığcam keçidlər | LAV-047 |
| 25 | Footer-dən operator keçidini çıxar və linkləri kiçikləşdir | LAV-048 |
| 26 | Ana lentin scroll və kart qarşılıqlı təsirlərini cilala | LAV-049 |

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

Ayrı rəy formu, rəyə birbaşa cavab, müəllif cavabı, eyni hekayədə davam, dəstək və rəy reaksiyası. Yalnız təsdiq edilmiş canlı uşaq materialı göstər. **Sübut:** rəy bağlama, rəy gizlətmə, paralel valideyn gizlətməsi və bildiriş təkrarı sınaqları.

**Tətbiq kontraktı:** Rəy, birbaşa cavab və müəllif cavabı mətnləri dəyişməz revision-larda gözləyir; yalnız moderatorun təsdiqlədiyi cari revision ictimai olur. Rəy sahibi anonim iştirakçı alias-ı, sessiya, seçilən cihaz cookie-si və rotasiya olunan birdəfəlik bərpa kodu ilə idarə edir. Bərpa sirri yalnız HMAC heşi ilə saxlanır. Rəy thread-i birbaşa cavab səviyyəsi ilə məhdudlaşır; cavaba cavab qəbul edilmir. Oxucu hər təsdiqlənmiş açıq rəydə sessiya üzrə unikal reaksiya verib geri götürə bilər. Müəllifin rəyə ayrıca bir cavab yeri qalır; düzəliş yeni pending revision yaradır. Davam yalnız etiraf sahibinə açıqdır. Rəyin açıq/bağlı seçimi yeni göndərişi bloklayır, əvvəl təsdiqlənmiş rəyləri saxlayır. Dəstək etiraf-sessiya iştirakçısı üzrə unikal aktiv reaksiyadır; say parent kilidi altında dəyişir. Moderasiya qərarı parent lock, target/revision lock, audit və outbox-u eyni tranzaksiyada yazır. Uşaq təsdiqi/gizlətməsi parent visibility version-ını artırır; parent gizlədilməsi bütün uşaq sorğularını public scope-dan çıxarır. Rəy bildirişləri 15 dəqiqəlik outbox qrup açarı ilə birləşir, davam başına bir hadisə yaranır. Outbox-da mətn yoxdur, çatdırılma LAV-009-a aiddir. İctimai detail-in iştirakçıya görə dəyişən HTML-i `private, no-store` olur.

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

## LAV-011 · PWA, oflayn və opt-in push

**Asılılıq:** LAV-002, LAV-004, LAV-009, LAV-010. **Qəbul:** PWA-01, ADV-03.

Manifest, ikon, standalone, statik service worker keşi, tərcüməli oflayn görünüş, opt-in cihaz qaralaması və Web Push. **Sübut:** təmiz/qədim keş, iPhone/Android standalone və həssas şəbəkə/keş audit nəticəsi.

## LAV-012 · Cihaz, UI və dil sınaqları

**Asılılıq:** LAV-006, LAV-007, LAV-008, LAV-010, LAV-011. **Qəbul:** UI-01, UI-03, UI-04, LOC-03.

Kiçik mobil, iOS Safari, Android Chrome, masaüstü və standalone. Gündüz/gecə, klaviatura, geri düyməsi, dəstək və şikayətlər. **Sübut:** viewport və dil bazası, ekran görüntüləri, cihaz/OS versiyası və faktiki nəticə.

## LAV-013 · GA4, CMP və AdSense

**Asılılıq:** LAV-006, LAV-007, LAV-009, LAV-010. **Qəbul:** ADV-01, ADV-02, ADV-03.

Səth whitelist, Consent Mode, təsdiqlənmiş reklam dili və məzmunu, bir manual slot, ads.txt, təcili söndürmə. **Sübut:** sintetik network trace-də əvvəl-sonra-geri alma, uyğun olmayan rəy və ayrıca rəy formu. İstehsal ID-ləri səlahiyyətli hesabla qoşulur.

## LAV-014 · Xarici axtarış yoxlaması

**Asılılıq:** LAV-006, LAV-008. **Qəbul:** SEO-03, SEO-04.

Search Console, Bing, IndexNow outbox, iş nəticəsi və retry, sitemap və status auditi. **Sübut:** testdə provider contract və retry. İstehsalda təsdiqlənmiş domen, sitemap və canlı bildiriş qeydi. İndeks mövqeyi xarici nəticədir.

IndexNow yalnız HTTPS `APP_URL` host-u üçün işləyir; `INDEXNOW_KEY` yoxdursa dispatcher heç nə göndərmir. Key faylı yalnız konfiqurasiya olduqda açılır. Outbox çatdırılması `lavbe:indexnow` scheduler əmri ilədir. Search Console/Bing property və sitemap canlı yoxlaması operatorun buraxılış qapısıdır; production sirri repository-yə əlavə edilmir. Rəsmi Bing sənədi legacy SOAP/POX API üçün 2026-08-31 retirement tarixini göstərdiyindən legacy submit route istifadə edilmir.

## LAV-015 · Shared hosting buraxılış sübutu

**Asılılıq:** LAV-010, LAV-011, LAV-012, LAV-013, LAV-014. **Qəbul:** OPS-01, OPS-02, OPS-03.

Real hosting, cron, SMTP, rollback, məlumatın geri gəlməməsi, 17 dilin istismar təminatı və istifadəçi axınları. Əvvəl staging, sonra yayım səlahiyyəti. **Sübut:** runbook, rollback və real yükləmə nəticəsi. Hesab və hüquqi təsdiqlər [əməliyyat qapılarına](operations.md) uyğun bağlanır.

## LAV-016 · Layihə üzrə hardcode auditi

**Asılılıq:** cari tətbiq. **Qəbul:** CFG-01.

Dəyişdirilə bilən məhsul limitləri, səhifələmə, cookie müddətləri, cursor TTL və rate-limit-lər `config/lavbe.php`-də mərkəzləşdirilir; forma və server yoxlamaları eyni dəyərləri oxuyur. Lokalizə olunan lent sıralama adları dil fayllarından gəlir. Route UUID/şikayət kontraktları təkrarsız pattern sinfində saxlanır. **Sübut:** konfiqurasiya ilə limit dəyişəndə server və forma uyğunluğu testləri, mövcud test/build və sənəd validatorları. `docs/architecture/hardcode-audit.md` qalan istisnaları və növbəti audit sahələrini qeyd edir. Stabil HTTP/HTML/XML, kriptoqrafik, RFC/ISO, verilənlər bazası enum/sxem və fixture protokolları məhsul tuning dəyəri olmadığından avtomatik config-ə daşınmır.

## LAV-030 · Oxucu kəşfi və açıq müəllif adı

**Asılılıq:** LAV-006, LAV-007, LAV-010. **Qəbul:** DISC-01, OWN-04, UI-05.

Ortaq başlıqda ürək nişanı cari sessiyanın dəstəklədiyi etirafların siyahısını açır. Siyahı bütün dillərdəki açıq etirafları əhatə edir, amma private/no-store/noindex qalır. Hər açıq etirafın altında eyni dildə, cari etirafın kateqoriyasından fərqli və bir-birindən təkrarlanmayan kateqoriyalardan ən çox üç tövsiyə göstərilir. Etiraf başlığı məcburidir; müəllif istəyə görə ad-soyadını yaza bilər. Ad təsdiqlənmiş revision-ın bir hissəsi olur və yalnız həmin revision açıq olanda görünür. Footer-dən hüquqi/standart səhifələrə keçid saxlanır. **Sübut:** session scope, cross-locale supported list, kateqoriya üzrə unikal public recommendation filters, approval-only public author, title validation və footer route feature testləri.

## LAV-031 · Etiraf bildiriş formalarının modal təqdimatı

**Asılılıq:** LAV-002, LAV-008. **Qəbul:** UI-06.

Etiraf detalındakı icma şikayəti və hüquqi bildiriş native dialog popup-da formanı dərhal göstərir. Paylaşılan form hissəsi ayrıca GET səhifələrdə də eyni qalır. `showModal()` dəstəyi olmadıqda və ya JavaScript sönülü olduqda href ayrıca forma səhifəsinə aparır. Server endpoint, CSRF, rate limit, private response headers və hüquqi/adi şikayət axınları dəyişmir. **Sübut:** dialog klaviatura/backdrop bağlanması və fokusun trigger-ə qayıtması, form POST davranışı və JS-siz fallback.

## LAV-032 · Modal popup-ların mobil/desktop uyğunlaşması

**Asılılıq:** LAV-031. **Qəbul:** UI-07.

Dialog en və hündürlüyünü vizual viewport-a bağla, kiçik ekranlarda safe-area kənarlarını saxla və uzun məzmunu öz daxilində skrolla. Başlıq və bağlama düyməsi skroll zamanı görünən qalsın; sahələr dar eni aşmasın. Masaüstündə dialog ortalanmış, oxunaqlı eni saxlasın. **Sübut:** build və 320/360/390/768/1366px enlər, aşağı/landscape hündürlüklərdə responsive UI baxışı.

## LAV-033 · Popup-ların bütün ekranlarda mərkəzlənməsi

**Asılılıq:** LAV-031, LAV-032. **Qəbul:** UI-08.

Mobil üçün tətbiq olunmuş alt kənar margin-ini ləğv et; dialog-u viewport-a fixed inset ilə bağlayıb bütün margin-ləri auto et. Kiçik hündürlükdə də safe-area boşluqları daxilində qalan max-height-i saxla; uzun form daxilən skrollansın. **Sübut:** build və portret, landşaft, masaüstü viewport-larda mərkəzlənmə.

## LAV-034 · Başlıq ikonlarının dil menyusundan sonra düzülməsi

**Asılılıq:** LAV-002, LAV-003, LAV-030. **Qəbul:** UI-09.

Header utility controls sırası: dil menyusu, axtarış, dəstəklənən etiraflar, görünüş rejimi. Mövcud label, route, active state və mobil responsivlik toxunulmaz qalır. **Sübut:** Blade header render-i və build.

## LAV-035 · Etiraf altında thread-li rəy və reaksiya

**Asılılıq:** LAV-007. **Qəbul:** UI-10, OWN-05, MOD-05.

Etiraf detalında rəy formu, hər açıq rəydə sessiya üzrə dəyişdirilə bilən bəyənmə və üst səviyyəli rəylərdə birbaşa cavab formu göstərilir. Thread yalnız bir cavab səviyyəsinə icazə verir. `parent_public_id` eyni etirafda açıq və təsdiqlənmiş kök rəyi göstərməlidir; server bunu həm göndərişdə, həm də moderasiya təsdiqində yenidən yoxlayır. Cavablar mövcud rəy moderasiyası və sahiblik/bərpa axınından keçir. Ana rəy gizlədiləndə açıq cavablar gizlənir, pending cavablar ləğv edilir; ana bərpa olunsa belə cavablar avtomatik dərc edilmir. Cavab önbaxışı məhdud saydadır və ayrıca səhifələnir; moderasiya növbəsində cavabın hədəf rəyi görünür. **Sübut:** inline forma, ana rəy yoxlaması, reaksiya idempotentliyi/geri götürmə, pending məxfiliyi, cavab moderasiyası, gizlətmə/bərpa keçidi, sənəd və build yoxlamaları.

## LAV-036 · Bütün artan siyahıların nömrəli səhifələnməsi

**Asılılıq:** LAV-003, LAV-007, LAV-009, LAV-030, LAV-035. **Qəbul:** PAG-01, UI-02.

Artan bütün istifadəçi və moderasiya siyahılarına mərkəzi ölçü, stabil ikinci sıralama açarı və paylaşılan responsiv idarə əlavə olunur. İlk, son və cari səhifələr, yaxın nömrələr, ellipsis, əvvəlki/növbəti, bölmə anchor-u və müstəqil query açarı işləyir. Axtarış paginasiya POST formu ilə sorğunu URL/referrer-dən kənar saxlayır; private siyahıların no-store davranışı qalır. Feed-dən sonsuz kart əlavə etmə UI-sı çıxarılır; ana/kateqoriya feed-i də nömrəli, adi HTML səhifələrindən istifadə edir. Moderasiya adminində hesab seçimi bütün verified user-ləri yaddaşa yükləmək əvəzinə axtarış və səhifələmə ilə işləyir. **Sübut:** public/account/moderation list controller və view-lər, invalid page cavabı, responsive control, build, sənəd və CI yoxlamaları.

## İş dövrü

Hər issue məqsədi, asılılığı, qəbul ID-si və sübutu daşıyır. Branch → commit → push → draft/hazır PR → CI → sahib baxışı. Sprint sonunda nəticə, blok, cavabdeh və növbəti prioritet yazılır. CI adı sabitləşəndən sonra main üçün yoxlama qaydası tətbiq edilir. GitHub statusunu sprint sənədində təkrarlama. Sənədlər push olunanda yeni Codex icrası avtomatik başlanmır.

## LAV-037 · Məşhur etiraflar lenti

**Asılılıq:** LAV-003, LAV-007, LAV-036. **Qəbul:** FEED-01, LOC-02, PAG-01.

Oxucu lentindəki “Davamı gələnlər” sıralamasını “Məşhur etiraflar” sıralaması ilə əvəz et. Xal bütün müddət üzrə aktiv dəstək reaksiyalarını və təsdiqlənmiş unikal açıq rəyçiləri mövcud çəki qaydası ilə hesablayır; bərabərlik yayım vaxtı və ID ilə həll olunur. Davam məzmunu etirafın detail səhifəsində qalır, fragment API-nin mövcud davam sırası isə geriyə uyğunluq üçün ayrıca saxlanır. 17 dilin etiketlərini və lent müqaviləsini yenilə. **Sübut:** sıralama sorğusu, dil paketləri, pagination/filter davranışı, build, Pint, sənəd validatorları və CI.

## LAV-038 · Header-də seçilmiş dilin native adı

**Asılılıq:** LAV-002, LAV-003, LAV-010, LAV-034. **Qəbul:** UI-11, LOC-01.

Desktop header-də cari locale kodunu gizlədib bayraq, tam native ad və dropdown işarəsi göstər. Mobil enlərdə native ad və dropdown işarəsini vizual gizlət, yalnız bayraq saxla; accessible name native dildən gəlməlidir. Axtarışlı seçim menyusunun native/ingilis adları, seçilmiş işarəsi və POST davranışı qorunur. Ekran spesifikasiyası və atlası uyğunlaşdır. **Sübut:** render edilmiş Blade, CSS build, atlas görünüşü, Pint və sənəd yoxlaması; ayrıca cihaz sınağı aparılıbsa nəticəsi göstərilir.

## LAV-039 · Azərbaycan nümunələri və Lavbe wordmark loqosu

**Asılılıq:** LAV-002, LAV-003, LAV-038. **Qəbul:** DEMO-01, BRAND-01, UI-01.

Yerli demo seeder-ə 12 sabit kateqoriya üçün bir-birindən fərqli, təbii Azərbaycan dilində uydurma etiraf əlavə et; əvvəlki hekayələri və təkrar çağırışın idempotentliyini saxla, production seed yoluna qoşma. Mövcud monoqramı açıq lisenziyalı Lucide Feather ikonu ilə əvəzlə, eyni SVG-ni favicon mənbəyi et. “Lavbe” wordmark-ına Caveat əl yazısı fontu tətbiq et, fontu lokal paketlə və lisenziyasını saxla. Ekran atlasını, dizayn sistemi, məhsul və backlog sənədlərini yenilə. **Sübut:** seeder mühit yoxlaması/idempotent nəticə, font/icon lisenziyası, build, Pint, Blade cache, sənəd yoxlaması və CI.

PR #85-də əlavə olunan Lucide/Caveat wordmark [LAV-041](#lav-041--təqdim-olunan-lavbe-logosu-və-lələk-favicon-u) ilə istifadəçinin verdiyi vektor logo ilə əvəz olunur. LAV-039-un Azərbaycan demo hekayələri saxlanır.


## LAV-040 · Kateqoriyaları yenilə və mövcud yas etiraflarını təsnif et

**Asılılıq:** LAV-003, LAV-010, LAV-039. **Qəbul:** TAX-01, LOC-02, DEMO-01.

İstifadəçinin təqdim etdiyi 12 mövzunu sabit daxili açarlara uyğunlaşdır. 17 dildə həm kateqoriya adlarını, həm izahlarını yaz; izahı kateqoriya lentində, yazı formasındakı bələdçidə və moderasiya baxışında göstər. Köhnə `grief` məzmunu yeni `life-changes` mövzusuna uyğundur; bir dəfəlik migration mövcud confession və revision kateqoriyalarını dəyişir, audit/outbox qeydlərinə toxunmur. Tarixi `grief` açarı yeni “Valideyn olmaq” mövzusuna təyin edilir; local demo seeder-in iki valideynlik nümunəsi əlavə olunur, əvvəlki yas nümunələri `life-changes`-də saxlanır. Migration mövzu açarı metadatasını dəyişir, confession mətnini və müəllif məlumatını oxumur və loglamır. **Sübut:** 17 locale üzrə catalog yoxlaması, data migration və seeder idempotentliyi, sənəd validatoru və CI.

## LAV-041 · Təqdim olunan Lavbe logosu və lələk favicon-u

**Asılılıq:** LAV-002, LAV-039. **Qəbul:** BRAND-01.

İstifadəçinin verdiyi şəffaf logo şəklini tam vektor konturlara çevir. Mövcud başlıq yerində bütöv lələk və “Lavbe” söz nişanını göstər; mövcud naviqasiya və digər ekranları dəyişmə. Əsas rəng və ürək vurğusu CSS dəyişənləri ilə idarə olunsun, SVG `currentColor` ilə rənglənə bilsin. Eyni logodakı lələk konturunu ayrıca SVG favicon və uyğun ICO fallback et. SVG-də raster/base64 yerləşdirmə olmasın. **Sübut:** SVG path quruluşu, açıq fon preview-u, favicon preview-u, Blade kompilyasiyası, build, sənəd validatoru və CI.

## LAV-042 · Kateqoriyalara unikal mövzu ikonları

**Asılılıq:** LAV-040. **Qəbul:** CAT-01.

12 sabit kateqoriya açarına mövzu mənasına uyğun, bir-birindən fərqli Lucide SVG seç. İkonları lokal açıq mənbə paketindən build zamanı sinxronlaşdır; naviqasiya çiplərində və etiraf kartlarında lokallaşdırılmış adla yanaşı göstər. Dekorativ ikonlar link adını əvəz etmir. **Sübut:** kataloqda ikon unikallığı, build və sənəd yoxlaması.

## LAV-043 · Unikal oxunuş və kateqoriya favicon-u

**Asılılıq:** LAV-006, LAV-042. **Qəbul:** READ-01, FAV-01.

Yalnız açıq/təsdiqlənmiş etiraf detail GET sorğusunda birinci tərəf brauzer profili cookie-si ilə oxunuş say. Xam tokeni yox, confession üzrə ayrıca HMAC heşi saxla; DB unikal məhdudiyyəti paralel sorğularda da dublikatı dayandırsın. IP, user-agent, referer və etiraf mətni ölçmə cədvəlinə daxil olmasın. Oxunuş sayını lent kartlarında və detail-də 17 dildə göstər. Kateqoriya lentində və həmin kateqoriyadakı etirafda kateqoriyanın öz yerli SVG-sini favicon et; ümumi səhifələrdə lələk qalsın. Cookie silinməsi, 365 gündən çox fəaliyyətsizlik və başqa profilin yeni oxucu kimi sayılması məhdudiyyətini, həmçinin kuki bildirişinin buraxılışdan əvvəl hüquqi baxışa ehtiyacını sənədləşdir. Etiraf daimi silinəndə oxucu heşləri və sayğac da silinir. **Sübut:** unikal/müxtəlif cookie feature sınaqları, count görünməsi, migration, favicon head, build, sənəd validatoru və CI.

## LAV-044 · Kartda oxunuş sayının yeri

**Asılılıq:** LAV-043. **Qəbul:** UI-12.

Lent kartında oxunuş göstəricisini yuxarıdakı kateqoriya/tarix metadata sətrindən çıxarıb kartın alt sağındakı footer-ə yerləşdir. Müəllif adını footer-in solunda saxla; dar mobil enlərdə elementlər səliqəli qırılmalı və oxunuş sağ kənara hizalanmalıdır. Etiraf detail səhifəsinin göstəricisinə toxunma. **Sübut:** HTML/feature yoxlaması, 320 px responsiv baxış, CSS build və sənəd validatoru.

## LAV-045 · Tövsiyələri kateqoriyalar arasında müxtəlifləşdir

**Asılılıq:** LAV-030. **Qəbul:** DISC-01.

Etiraf detail-ində cari etirafı və onun kateqoriyasını çıxar. Eyni dildəki açıq/təsdiqlənmiş etiraflardan hər kateqoriya üçün dəstək sayı, yayım vaxtı və ID-yə görə ən yaxşı namizədi seç; kateqoriya başına yalnız birini götür, ən güclü üç fərqli kateqoriyanı göstər. Mövcud kart görünüşü, locale filter-i və public scope saxlanır. **Sübut:** kateqoriya təkrarı, cari kateqoriya, dil sərhədi, sıralama və maksimum say feature sınaqları, build, sənəd validatoru və CI.

## LAV-046 · Footer-i əlçatan və responsive standartlara uyğunlaşdır

**Asılılıq:** LAV-002, LAV-030. **Qəbul:** UI-05.

Footer-i mövcud Lavbe wordmark-ı və lokallaşdırılmış təsviri ilə saytın vizual dilinə uyğunlaşdır. Bütün yeddi mövcud hüquqi/məlumat səhifəsini cari dildə adlandırılmış, ayrıca naviqasiya landmark-ında göstər; cari səhifəni `aria-current` ilə işarələ. Mobil grid, 44 px toxunma hündürlüyü, görünən fokus, rəng kontrastı, mətnin böyüməsi və safe-area/fixed mobil naviqasiya boşluğunu qoruyub 320 px ekranda yoxla. Operator, müəllif hüququ və əlaqə barədə təsdiqlənməmiş fakt əlavə etmə. **Sübut:** bütün 17 locale üçün link və səhifə feature yoxlaması, responsive CSS build, Blade cache və sənəd validatoru.

## LAV-047 · Footer-də ürəkli Lavbe loqosu və sağda yığcam keçidlər

**Asılılıq:** LAV-046. **Qəbul:** UI-05.

Footer-i sol wordmark və sağda yığcam, əlçatan adlandırılmış hüquqi keçidlərlə bir sırada göstər. Wordmark-da Lavbe-nin “v” hərfinin yerindəki ürək vurğusu kiçik ölçüdə də tanınsın. Çıxarılmış “Lavbe · Etiraflar Vadisi” signature sətrini göstərmə. Altı lokallaşdırılmış link, cari səhifənin `aria-current` işarəsi, 44 px hədəf, mobil qırılma və alt safe-area boşluğu saxlanır. **Sübut:** HTML/feature sınağı, CSS build, 320 px və desktop vizual baxış, sənəd yoxlaması.

## LAV-048 · Footer-dən operator keçidini çıxar və linkləri kiçikləşdir

**Asılılıq:** LAV-047. **Qəbul:** UI-05.

Footer-də `operator` keçidini göstərmə; operator məlumatı səhifəsinin marşrutu birbaşa URL ilə əlçatan qalsın. Qalan altı linkin şriftini `.88rem`-dən `.8rem`-ə endir, 44 px toxunma hündürlüyünü və bütün responsive/əlçatan davranışı saxla. **Sübut:** 17 locale üzrə footer-də operator keçidinin olmaması və səhifənin HTTP 200 cavabı, CSS build, sənəd validatoru.

## LAV-049 · Ana lentin scroll və kart qarşılıqlı təsirlərini cilala

**Asılılıq:** LAV-002, LAV-003. **Qəbul:** UI-13.

Mobil mövzu zolağına nazik, görünən scrollbar, üfüqi overscroll containment və proximity scroll snap əlavə et; mövcud responsive naviqasiya və URL-lər dəyişmir. Kartlarda yalnız hover-capable pointer üçün yumşaq sərhəd/kölgə, klaviatura fokusu üçün focus-within halqası göstər. Transform və davamlı animasiya əlavə etmə; global reduced-motion qaydasını saxla. **Sübut:** desktop/mobile browser baxışı, focus/hover CSS, CSS build və sənəd validatoru.
