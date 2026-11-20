# Sahiblik, moderasiya və yayım müqaviləsi

Bu sənəd əməliyyatların və paralel sorğuların istinadıdır. İcma qaydaları və məlumat siyasəti [moderasiya və məxfilik](../moderation-privacy.md), ekran davranışı [ekran spesifikasiyasında](../ui/screens-and-states.md) saxlanır.

## Sahiblik və səlahiyyət

| Əməliyyat | İcazə | Qəti şərt |
|---|---|---|
| Etiraf, rəy, reaksiya və şikayət göndər | Qonaq və ya təsdiqlənmiş hesab | Yazı və rəy üçün yaş siyasəti, spam limiti və CSRF. Rəy üçün etiraf açıq və rəylər aktivdir. |
| Etirafı və öz rəyini redaktə et, geri çək və sil | Hədəfin sahibi | Serverdə konkret hədəf üzrə yoxlama. Bir etirafda sahiblik başqa iştirakçının rəyinə şamil edilmir. |
| Davam yaz, müəllif cavabı ver, rəyləri açıb bağla | Etiraf sahibi | Açıq etiraf. Cavab yalnız görünən rəyə və bir səviyyə dərinliyində. |
| Yayımla, rədd et, düzəliş istə, təcili gizlət | Hədəfin dili üzrə moderator | Hədəf, versiya, gözlənən vəziyyət, səbəb, audit. Admin rolu öz-özünə moderasiya dil icazəsi yaratmır. |
| Moderator icazəsini dəyiş | Admin | MFA, yenidən giriş təsdiqi, audit qeydi. |
| Təkrar baxış istə | Hədəfin sahibi | Qərar üzrə bir sorğu. Mümkündürsə əvvəlki qərarı verən moderatordan fərqli səlahiyyətli şəxs baxır. |

LAV-008 report qrupları confession/comment hədəfini və community/legal kanalını ayrı saxlayır. Eyni reporter həmin target+channel üzrə bir dəfə sayılır. Təcili prioritet threat səbəbindən serverdə yüksəlir; mass-report count visibility state-i dəyişmir. Report/appeal izahları şifrəli, reporter/owner bağları HMAC-dır. Hide və dismissal report auditini hədəf qərarı ilə eyni DB transaction-da yazır. Etiraz `hide_audit_id`-yə unikaldır və müəllif sahiblik sübutu tələb edir.

LAV-009 account yalnız email-i təsdiqlənəndən sonra şəxsi bölmə, follow və transfer aça bilər. Guest confession və comment hərəsi öz owner cookie/session sübutu ilə ayrıca iddia olunur; parent əvvəl, target/ownership sonra kilidlənir. Eyni user üçün təkrar claim təhlükəsiz no-op, başqa user-in claim-i `409` olur. Bağlama atomik `user_id` verir, guest session/token hash-lərini təmizləyir və non-null cookie/recovery hash-lərini əlçatmaz təsadüfi heşlə əvəz edir. Bound ownership üçün yalnız həmin user səlahiyyətlidir, əvvəlki cookie artıq kifayət etmir. Follows user scope-da saxlanır və public confession silinəndə cascade olur. İstifadəçi bildirişi outbox event/user üzrə unikaldır; email yalnız verified user opt-in etdikdə göndərilir. Bildiriş sətri event hash, event növü, kanal seçimləri və çatdırılma statusundan ibarətdir, məzmun və public ID saxlamır.

Moderator əməliyyatında guard, authorization policy və service layer eyni qaydanı tətbiq edir: autentikasiya olunmuş aktiv moderator, resurs dilinə uyğun aktiv grant, sessiyada keçərli MFA freshness və aktiv hədəf/revision. `users` sətrində admin flag olması təkbaşına moderasiya və ya dil icazəsi vermir. Səlahiyyət dəyişməsi ayrıca admin əməliyyatıdır, təzə MFA, səbəb və audit tələb edir. MFA TOTP sirri application key-dən ayrılmış key material ilə authenticated encryption formasında saxlanır; recovery kodları yalnız hash-lə saxlanır, göstərildikdən sonra təkrar qaytarılmır.

İctimai UUID və ya opaque ID bir resursu tapır. `Lavbe #XXXXXXXX` yalnız göstərilən addır. Sessiya, hesab sahiblik qeydi və etiraf üzrə bərpa sirri serverdə ayrı saxlanır. Qonaq iddiası yalnız sahiblik sübutundan sonra hesaba bağlanır. Etirafın mətni, URL-si, ictimai aliası və şikayətin izahı sahiblik sübutu hesab edilmir.

Cihaz yaddaşı seçilməyibsə idarəetmə cari sessiyada və bərpa vasitəsilə mümkündür. Cihaz yaddaşı seçiləndə persistent opaque cookie üzrə icazə yazılır. Hər qonaq etirafının ayrıca, kriptoqrafik təsadüfi ən az 128 bitlik bərpa sirri var. Hesaba bağlanmamış qonaq rəyi üçün eyni model rəy səviyyəsində ayrıca bərpa sirri verir. Bərpa sorğusu `POST` ilə edilir, limitlənir, cavab ümumi olur. Yalnız uğurlu bərpa yeni sirrləri yaradır və həmin hədəfdə əvvəlki sahiblik vasitələrini ləğv edir. Paralel bərpa cəhdlərindən yalnız biri uğur qazana bilər. Hesaba bağlanan hədəfin qonaq açarları ləğv olunur. Hesab bərpası təsdiqlənmiş e-poçt vasitəsilə bir dəfəlik və müddətli keçiddir.

LAV-004-də ilk qonaq göndərişinin məzmunu yalnız `pending` immutable revision-da saxlanır; etirafın public read-model sahələri boş, `status=unpublished`, `published_at=NULL` qalır. Cari Laravel session ID-si sahiblik sətrində yalnız `HMAC-SHA-256` heşi ilə bağlanır. İstəyə bağlı 256-bit owner cookie və bərpa sirri də DB-də yalnız heşlə saxlanır. Cihazı yadda saxlamaq seçilərsə cookie həmin etirafın UUID-si əsasında adlandırılan HttpOnly/Secure/SameSite cookie-də verilir. Recovery tranzaksiyası etiraf və ownership sətrini kilidləyir, uğurlu yoxlamadan sonra session ID-sini yeniləyir, tokenləri bir atomik dəyişiklikdə rotasiya edir və `credential_version` artırır. Başlıq və mətn yalnız yeni revision yaradılaraq dəyişə bilər; mövcud revision mətn sahələri ORM səviyyəsində dəyişməzdir.

LAV-058-dən etibarən yeni yadda saxlanan cihaz bütün etirafları üçün eyni 256-bit `lavbe_device` HttpOnly/Secure/SameSite cookie-dən istifadə edir; yeni etiraf başına cookie verilmir. `device_token_hash` verilənlər bazasında yalnız HMAC heşi kimi saxlanır. `/{locale}/my/device` şəxsi, `no-store/noindex` səhifəsi cookie ilə uyğun gələn qonaq etiraflarının başlığını və moderasiya vəziyyətini səhifələyərək göstərir. Cihaz tokeni etiraf sahiblik sübutlarından biridir və həmin cihazdan sahiblik əməliyyatlarına imkan verir. Etiraf hesaba bağlananda cihaz heşi ləğv edilir. “Cihazı unut” həmin açara bağlı guest ownership sessiya/token heşlərini rotasiya edib ümumi və brauzerdə olan legacy cookie-ləri təmizləyir, bərpa heşini saxlayır. Cookie itərsə məxfi bərpa kodu ayrıca işləyir.

## Model və keçidlər

Etirafın məcmusu üç ayrı dəyər saxlayır: görünürlük, cari açıq versiya və aktiv baxış işi. Açıq versiya dəyişməyən mətn nüsxəsidir. Baxış versiyası öz açarına malikdir. Görünürlük dəyərləri `unpublished`, `public`, `withdrawn`, `hidden`, `deleted`-dir. Baxış dəyərləri `pending`, `changes_requested`, `rejected`, `approved`, `cancelled`-dır. Aşağıdakı qayda bütün başqa keçidlər üçün rədd cavabını nəzərdə tutur.

| Hadisə | Əvvəlki hal | Sonrakı hal və yan təsir |
|---|---|---|
| İlk göndəriş | Mətn hələ yaradılmayıb | `unpublished` və bir `pending` versiya. Açıq URL 404. |
| Redaktə göndər | `unpublished`, `public`, `withdrawn` | Yeni `pending` versiya açılır, əvvəlki aktiv iş ləğv edilir. `public` olduqda əvvəlki mətn açıq qalır. `withdrawn` vəziyyətində müəllif yenidən yayım istədiyini ayrıca təsdiqləyir. |
| Təsdiqlə | `pending` cari versiya, görünürlük `unpublished`, `public` və ya yenidən yayım istənmiş `withdrawn` | İş `approved`, açıq pointer həmin versiyaya keçir, görünürlük `public`. Sitemap, lent və reklam uyğunluğu yenilənir. |
| Düzəliş istə və ya rədd et | Cari `pending` | İş `changes_requested` və ya `rejected`. Varsa əvvəlki açıq pointer qalır. Müəllif səbəbi özəl görür. |
| Müəllif geri çəkir | `unpublished` və ya `public` | `withdrawn`, cari pending iş `cancelled`, açıq HTML və preview 404. Sitemap və açıq materiallarda görünmür. |
| Moderator gizlədir | `public` və ya `withdrawn` | `hidden`, aktiv baxış ləğv edilir, ictimai 404 və sitemap-dən çıxarılma. Sonrakı redaktə özü-özünə görünürlüyü dəyişmir. |
| Gizlətməyə etiraz qəbul olunur | `hidden`, səlahiyyətli moderator | Əvvəlki təsdiqlənmiş versiya yalnız ayrıca reklam və məzmun yoxlamasından sonra bərpa oluna bilər. Müəllif əvvəl geri çəkibsə, `withdrawn` qalır. |
| Daimi silmə təsdiqlənir | `unpublished`, `public`, `withdrawn`, `hidden` | `deleted` terminaldır. Əvvəllər açıq olmuş URL 410, açıq olmamış ünvan 404. Açıq cavabları sil, zəruri məhdud audit metadatasını siyasət üzrə saxla. |

Rəy və davam öz canlı versiya göstəricisinə malikdir. Valideyn etiraf açıq olmayanda uşaq məzmunu ictimai oxunmur. Rəyi gizlətmək ona bağlı müəllif cavabını da gizlədir. Aydın yoxlama olmadan vaxtilə gizlədilmiş material yenidən açılmır. Rəy və davam ayrı canonical səhifə almır.

## Tranzaksiya və yarış qaydası

1. Etiraf və ya rəy üzrə əməliyyatı səlahiyyət yoxlamasından sonra tranzaksiyada apar. Ana etirafı və sonra uşaq qeydlərini sabit sıra ilə kilidlə. Moderator əmri hədəf ID-si, aktiv versiya ID-si və gözlənən dəyişiklik sayğacı daşıyır.
2. Köhnə əməliyyat `409` qaytarır və heç bir ictimai yan təsir doğurmur. Səlahiyyətsiz şəxsin hədəfi yoxlaması neytral `404` alır. Formada etibarsız məlumat `422`, rate limit `429` alır. Təkrar idempotency açarı eyni aktor və eyni yük olduqda eyni nəticəni qaytarır, fərqli yükdə `409` olur.
3. Görünürlük, açıq versiya, reklam üçün cari uyğunluq, keş nəsli, sitemap mənbə vəziyyəti və outbox eyni tranzaksiyada dəyişir. İctimai oxu cari committed görünürlük və keş nəslini yoxlayır. Köhnəlmiş keş ictimai cavabı bərpa edə bilməz. Yanlış purge ehtimalı olan hostda açıq etiraf HTML-i kənar keşə verilmir.
4. Xarici IndexNow, e-poçt və Web Push hadisələri ayrı, təkrar icra edilə bilən outbox işləridir. Köhnə hadisə daha yeni görünürlüyü geri qaytara bilməz. Yan təsir yalnız məzmunun cari versiyası ilə uyğun olduqda tətbiq olunur.
5. Geri çəkmə ilə təsdiq yarışında təsdiq birinci bitərsə sahibin monotonic geriçəkməsi köhnə lock version-la da son vəziyyəti bağlayır. Geri çəkmə birinci bitərsə köhnə moderator təsdiqi `409` alır. Başqa moderator qərarlarında köhnə lock version həmişə `409` alır. Rəy təsdiqi ilə etirafın gizlədilməsi yarışında açıq rəy görünmür.
6. Daimi silmənin bərpa izi mətnsiz opaque ID və zaman saxlayır. Ehtiyat nüsxədən ayrı qorunur. İctimai trafik açılmadan gizlətmə və silmə jurnalı tətbiq edilir. İz tamlığı yoxlanmayıbsa bərpa mühiti qapalı qalır.

### Moderator qərar müqaviləsi (LAV-005)

- Hər komanda POST olur və `target_id`, `revision_id`, `expected_lock_version`, qərar, tələb olunan səbəb və unikal idempotency key daşıyır. Bu sahələrdən heç biri səlahiyyət sübutu deyil. İctimai ID/opaque URL yalnız hədəfi seçir.
- Server transaction-da moderator grant-ını və MFA freshness-i, sonra parent confession-i `FOR UPDATE` kilidləyir; active review/revision və lock version-u yenidən oxuyur. Rəy target-i varsa child bundan sonra sabit ID sırası ilə kilidlənir. Gözlənən hal dəyişibsə `409`, səlahiyyət və ya MFA yoxdursa neytral `404`/`403` policy cavabı qaytarılır; dəyişiklik, audit və outbox yazılmır.
- Revision mətninin özünü dəyişmək olmaz. Müəllif düzəlişi yeni revision yaradır; moderator isə baxdığı revision-ın yalnız review statusunu dəyişir. `approved` yalnız `public_revision_id` pointer-ını həmin revision-a keçirə bilər. Başqa qərarlar əvvəlki public pointer-ı saxlayır və pending mətni public read model-ə köçürmür.
- Tək transaction-da revision/review statusu, confession visibility və `visibility_version`, current public revision pointer, append-only audit event və idempotent discovery outbox event commit edilir. Outbox event target ID, event type, visibility version, public revision ID/hash, locale/category və event timestamp-dan ibarət olur; confession text, actor secret, recovery/session token, email, raw IP daxil deyil. `hide`/`withdraw` event əvvəlki `publish` event-dən yüksək visibility version daşıyır və consumer köhnəsini tətbiq etməz.
- Audit event-də actor user ID-si, o vaxtkı rol/dil grant snapshot-ı, target/revision, previous/new state, reason code, sanitizasiya olunmuş daxili və müəllifə görünən səbəblər (ayrıca sahələr), request/idempotency fingerprint, expected/resulting lock version, əvvəlki/yeni public revision hash və timestamp saxlanır. Heç bir sərbəst request payload-ı və ya confession body auditə kopyalanmır. Şəxsi müəllif cavabı ayrıca privacy policy ilə qorunur.
- Qərarlar: `approve`, `request_changes`, `reject`, `hide`, `restore`, `terminal_delete`. `restore` yalnız əvvəl təsdiqlənmiş versiyanı, grant + MFA + fresh review yoxlandıqdan sonra aça bilər; `terminal_delete` heç vaxt public pointer-ı yenidən qura bilməz. Öz səlahiyyət grant-ını dəyişən moderator üçün əməliyyat qadağandır.
- İnteqrasiya testində köhnə moderator formu, eyni revision-a iki approve, approve ilə owner withdraw, approve ilə moderator hide, hide ilə outbox publish, terminal delete ilə köhnə event yarışı MySQL-də yoxlanır. Eyni expected version üzrə yalnız bir qərar keçir; son committed owner withdrawal/hide/delete ictimai oxuda qalibdir. Outbox-ın publish edilməsi üçün xarici HTTP çağırışı DB transaction-dan sonra baş verir.

[Sınaqlar: OWN-01, OWN-02, OWN-03, MOD-01, MOD-02, MOD-03, MOD-04 və OPS-02](../delivery/acceptance.md)
