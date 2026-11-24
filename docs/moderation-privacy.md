# Moderasiya, təhlükəsizlik və məxfilik

Tam keçidlər və paralel əməliyyatlar üçün [sahiblik və yayım müqaviləsi](architecture/ownership-and-publication.md) əsas götürülür.

## Vəziyyət və moderator səlahiyyəti

| Əməliyyat | Nəticə |
|---|---|
| İlk göndəriş | Baxışda. Müəllif statusu özəl bölmədə görür. Açıq HTML, lent, sitemap və reklam yoxdur. |
| Yayımla | Cari versiya və kateqoriya açıq olur. AdSense statusu ayrıca qiymətləndirilir. |
| Düzəliş istə / rədd et | Məxfi səbəb. Yeni variant eyni işin yeni versiyasıdır. |
| Redaktəyə bax | Yalnız yeni versiyaya qərar ver. Əvvəlki açıq versiya müəllif geri çəkənədək və ya yeni versiya təsdiqlənənədək qalır. |
| Geri çək / təcili gizlət | Açıq versiya, preview, feed, sitemap və reklam çıxarılır. Müvəqqəti açıq ünvan 404. Yenidən açılma yalnız uyğun icazə və təsdiqlə. |
| Daimi sil | Ayrı müəllif təsdiqi və ya səlahiyyətli hüquqi qərar. Əvvəllər açıq olmuş ünvan 410, açıq olmamış ünvan 404. Bərpa proseduru məzmunu yenidən göstərmir. |
| Rəyi gizlət | Hədəf rəy və aid müəllif cavabı çıxarılır. Etirafın öz görünürlüyü ayrıca qərardır. |
| Etiraz | Bir dəfə səbəbli yenidən baxış. Təhlükəsiz olduqda ilkin moderator xaricində səlahiyyətli baxış. |

Panel bölmələri Etiraflar, Rəylər, Şikayətlər, Etirazlar və heyətin etiraf yazma formasıdır; admin üçün səlahiyyət və sayt inteqrasiyası idarəsi də var. Rollar və dil səlahiyyəti serverdə yoxlanır. Admin girişində MFA, moderasiya əməliyyatlarında səbəb və audit, nadir səlahiyyət dəyişikliklərində yenidən giriş təsdiqi tələb olunur. Lokal MFA istisnası yalnız `APP_ENV=local` və ayrıca açıq flag ilə mümkündür. Qərar kartında adi istifadəçinin IP və e-poçtu göstərilmir.

LAV-005 moderator panelində TOTP əsaslı MFA və birdəfəlik bərpa kodları var. TOTP açarı `MODERATOR_MFA_KEY` ilə ayrıca şifrələnir; bu açarı `APP_KEY`-dən fərqli və ehtiyat nüsxəsi qorunan sirr kimi saxla. İlk admin yalnız `LAVBE_ALLOW_ADMIN_BOOTSTRAP=true` olduqda, mövcud təsdiqlənmiş hesab üçün `php artisan moderator:bootstrap-admin USER_ID --confirm` əmri ilə verilir. Əməliyyatdan sonra bootstrap parametrini söndür. Hesab və MFA sirri seed edilmir.

İctimai etirafın unikal oxunuşu yalnız oxucunun ayrıca razılığından sonra ölçülür. Razılıqdan əvvəl və ya imtinada oxucu cookie-si/heşi yaradılmır, sayğac artmır; geri götürmə cookie-ni silir və yeni ölçməni dayandırır. Razılıqdan sonra təsadüfi 256-bit dəyər birinci tərəf HttpOnly, SameSite=Lax cookie-də 365 gün saxlanır və uyğun detail oxunuşunda müddəti yenilənir. DB-də xam cookie yox, hər etiraf üçün ayrıca açarla bağlı HMAC heşi saxlanır; `(confession_id, reader_hash)` unikal məhdudiyyəti paralel sorğularda təkrar sayımı dayandırır. APP_KEY rotasiyası əvvəlki cookie heşlərini yeni oxucu kimi göstərə bilər. Prefetch/prerender və tanınan crawler/preview user-agent-ləri sayılmır; user-agent yalnız həmin filtri seçmək üçün soruşulur, saxlanmır. IP, referer və etiraf mətni də bu sayğac üçün qeyd edilmir. Cookie profili silinərsə, bir ildən artıq fəaliyyətsiz qalarsa və ya brauzer/cihaz dəyişərsə həmin istifadəçi yeni oxunuş kimi sayılacaq. Etiraf daimi silinərkən ona bağlı oxucu heşləri silinir və həmin etirafın sayğacı məzmunla birlikdə silinir. Bu, fiziki cihaz identifikatoru deyil. Məqsəd və müddət 17 dilin kuki/məxfilik bildirişində izah olunur; faktiki region hüquqi baxışı buraxılış qapısıdır.

Panel və bütün moderator POST cavabları `private, no-store`, noindex və no-referrer başlıqları daşıyır. Moderatorun hər dili ayrıca aktiv grant tələb edir; aktiv `admin/*` bütün 17 dildə moderasiya və yazı icazəsi verir. Heyətin yeni etirafı mövcud approve audit və outbox yolu ilə birbaşa yayımlanır; qonaq yazısı pending qalır. TOTP son istifadə counter-i təkrar qəbul olunmur, MFA sessiya təsdiqi 5 dəqiqə qüvvədədir. Dərc, gizlətmə, bərpa və terminal silmə eyni tranzaksiyada versiya, audit və outbox qeydi yaradır. Outbox istehlakçısı görünürlük nəsilini yoxlamalıdır.

## Məzmun və spam

Dərc qaydası şəxsi məlumatın ifşası, müəyyən edilə bilən şəxsə qarşı ittiham və təqib, təhdid, istismar, qrafik seksual məzmun və spamı əhatə edir. Kontekst qərarın əsas hissəsidir. Öz hissini ifadə edən və başqasına hücum edən mətn ayrıca qiymətləndirilir. Məxfilik pozuntusu mümkün olduqda qərarın kənar mediaya, metadata və şikayət detallarına təsirini yoxla. Yayıma uyğun, reklama uyğun olmayan mətn açıq və reklamsız qala bilər.

Etiraf və rəy şikayətinin düyməsi, hədəfi və təsdiqi ayrıdır. Səbəblər spam/reklam, məxfilik, təqib/təhqir, təhdid/təhlükə, uyğunsuz məzmun və digər. İzah istəyə bağlıdır. Eyni hədəf üzrə şikayətlər bir moderator işində toplanır. Hesab və ya qonaq üzrə təkrar siqnal kəsilir. Limitlər fəaliyyət üzrə fərqlidir. IP təkbaşına blok qərarı deyil. Bot yoxlaması riskdə işə düşür. İstismar siqnalları yalnız moderasiya üçündür. Kütləvi şikayət sayı təkbaşına silmə əmri vermir.

LAV-008 icrasında icma və hüquqi bildirişlər ayrı kanal və növbələrdir; hüquqi səbəb və ya təhdid prioriteti serverdə təyin edilir. Şikayət izahı DB-də tətbiq şifrələməsi ilə saxlanır. Reporter HMAC-si təkrar yoxlaması üçündür; xam IP, sessiya ID-si, email və token saxlanmır. Hədəf+kanal üçün eyni reporterdən eyni fakt bir dəfə sayılır; fərqli səbəb və ya izah yeni fakt kimi saxlanır, bağlanmış qrup yenidən açılır. Təcili təhlükə icma növbəsində üstün göstərilir. Moderator növbəsi yalnız həmin dil üçün aktiv grant və beş dəqiqəlik MFA ilə açılır. Dismiss hədəfin statusuna toxunmur; hide, hədəfin mövcud qərarı, şikayət qrupu və audit eyni tranzaksiyada commit olunur. Gizlətmə qərarının sahibi gizlədilən məzmun üzrə hide auditinə bir dəfə izahlı etiraz göndərə bilər. Etiraz detalları da şifrələnir və yalnız grantlı moderator görür; mövcud olduqda başqa təsdiqlənmiş moderator baxır. Bərpa əvvəl təsdiqlənmiş versiyanı moderation/outbox tranzaksiyası ilə qaytarır. Hüquqi SLA və operatorun faktiki cavab imkanını ayrıca əməliyyat sübutu olmadan vəd etmək olmaz.

Hədəf adi baxış üçün 24 saat, təcili ifşa və təhdid üçün 4 saat ərzində ilkin baxışdır. İctimai vəd yalnız əməliyyat bunu təmin etdikdə göstərilir. Hər dil üçün cavabdeh və əvəzləyici təyin olunur. Mətn üçün üçüncü tərəf tərcümə və ya moderasiya servisinə göndəriş ayrıca məlumat axını və operator qərarı tələb edir.

## Məlumat və hüquqi hazırlıq

- Təsdiqlənən açıq etiraf ictimai və axtarışa açıq ola bilər. Forma bunu göndərişdən əvvəl bildirir. Giriş e-poçtu, təhlükəsizlik siqnalları və sahiblik məlumatı ayrıdır.
- Etiraf başlığı və mətni yalnız istifadəçi cihazda saxlama seçimini açanda brauzerin yerli yaddaşında saxlanır. Seçim açıqdırsa formaya qayıdanda bərpa olunur; 7 gün sonra, əl ilə silinəndə, hər iki sahə boşaldılanda və ya göndəriş təsdiqlənəndə silinir. Köhnə avtomatik qaralama açıq formada göstərilmir; cari səhifədə ayrıca bərpa seçimi verilib köhnə yaddaş qeydi silinir. Başlanğıc konfiqurasiyası rədd olunan mətn üçün 30 gün, qısa spam siqnalı üçün 14 gün, dövr edən backup üçün 30 gündür. Faktiki hüquqi əsas, istisna və məhdud saxlanma təsdiqlənir.
- Sirləri, forma yükünü, təsdiqlənməmiş mətni, etiraf önizləməsini və email-i loglara ötürmə. Moderator bildirişində və ekran görüntüsü fixture-larında sintetik mətn istifadə et. Şəxsi ekranlarda üçüncü tərəf analitikası və reklam yoxdur.
- LAV-004 yazı, bərpa və sahiblik cavabları `private, no-store`, noindex və no-referrer başlıqları daşıyır. Etiraf mətni yalnız pending revision-da saxlanır; feed və axtarış yalnız public read-model qeydlərini oxuyur. Bərpa sirri və session owner tokeni URL, HTML form action, analytics, public response və tətbiq loguna daxil edilmir; bərpa sirri yalnız bir uğurlu cavabda göstərilir.
- Yazı və bərpa limitləri 60 saniyəlik, əməliyyat üzrə HMAC edilmiş IP bucket-indən istifadə edir. Xam IP limiter açarında və tətbiq logunda saxlanmır; bu qısa rate limit moderator qadağası və ya IP üzrə blok qərarı deyil.
- Hesab e-poçtu təsdiqlənəndən sonra şəxsi bölmə açılır. Guest etiraf və rəy hərəsi yalnız həmin hədəfin mövcud session/cookie sahibliyi ilə ayrıca hesaba bağlanır; claim zamanı köhnə cookie və recovery dəyərləri ləğv edilir. E-poçt bildirişi ilkin olaraq söndürülüb və ayrıca opt-in tələb edir. Account notification body yalnız “izlədiyin etirafda yeni fəaliyyət var” deyir; etiraf/rəy mətni, başlıq, anonim ad, etiraz izahı, ID və bərpa sirri e-poçt, in-app row və analitikaya daxil edilmir. Private hesab səthləri no-store, noindex və no-referrer-dir.
- Səhifələr: İstifadə şərtləri, İcma və yayım qaydaları, Məxfilik bildirişi, Kuki/reklam seçimləri, Qanunsuz məzmunu bildir, Məlumat sorğusu/silmə və Operator/əlaqə. Hüquqi bildiriş adi şikayət formasından ayrıdır. Faktiki operator, məlumat təminatçıları, ötürmələr, hüquqi əsaslar, xüsusi kateqoriyalar, saxlanma, DPIA və bazar üzrə yaş/uşaq təhlükəsizliyi hüquqi baxışa daxil edilir.
- 18+ paylaşım məhsul qərarıdır. Bazar üzrə faktiki tətbiqi və açıq oxu riskini təsdiqlə. Müəllif məxfi kodu itiribsə, məlumat/silmə sorğusu mümkün olur. Qərar müəllifə redaktə hüququ vermir.

İstinadlar: [GDPR](https://eur-lex.europa.eu/eli/reg/2016/679/oj/eng) · [DSA](https://eur-lex.europa.eu/eli/reg/2022/2065/oj/eng) · [OWASP sessiya idarəetməsi](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
