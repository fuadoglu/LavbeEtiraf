# Qəbul matrisi

| ID | Ssenari | Gözlənən nəticə |
|---|---|---|
| BAS-01 | Təmiz checkout və MySQL ailəsi ilə CI | Asılılıqlar lock faylından qurulur, smoke test və sənəd yoxlaması keçir, prod mühit açarı tələb olunmur |
| UI-01 | Ana, mövzu, 404 və admin | Ortaq komponentlər, Lucide, fokus, gündüz/gecə və sabit mobil quruluş |
| UI-02 | Rusca Ailə lenti, şəbəkə kəsilməsi və təkrar | HTML-də rusca kartlar, skelet eyni ölçüdə, köhnə kartlar qorunur, təkrar ID yoxdur, JS olmadan HTML səhifələmə |
| UI-03 | 320 px, iPhone Safari, Android Chrome, standalone | Klaviatura kursoru örtmür, mətni xəta silmir, geri keçid mövqeyi mümkünsə bərpa edir |
| LOC-01 | İlk giriş, əl ilə dil və birbaşa URL | Seçim ardıcıllığı işləyir, seçilmiş dil yadda qalır, konkret URL ölkəyə görə yönləndirilmir, axtarış native və English adları tapır |
| LOC-02 | 17 paket və 12 kateqoriya | Açarlar, placeholder, slug, yazı və hüquqi səhifə mətnləri tamdır, sahibin baxış versiyası ayrıdır |
| OWN-01 | Qonaq etirafı, yaş təsdiqi, kod bərpası və başqa cihaz | Yalnız pending revision yaranır və feed/axtarışda görünmür, eyni sorğu dublikat yaratmır və sirri təkrarlamır, alias ayrıdır, saxta sahiblik rədd edilir, bərpa köhnə icazəni dayandırır |
| OWN-02 | İstəyə bağlı hesab, bildiriş və rəy sahibliyi | E-poçt təsdiqi və parol bərpası işləyir; şəxsi sahə yalnız təsdiqli hesaba açılır; etiraf/rəy öz qonaq sahiblik sübutu ilə ayrı-ayrılıqda bir hesaba atomik bağlanır və köhnə guest sirləri ləğv edilir; başqa hesab məlumatı görə və idarə edə bilmir; izləmə və seçimlər təkrara davamlıdır; bildiriş outbox-u idempotent işləyir |
| MOD-01 | İlk yayım, redaktə, geri çəkmə və rədd | Yalnız təsdiqlənmiş versiya görünür, köhnə moderator qərarı yeni versiyaya tətbiq edilmir; moderator aktiv hesabdır, MFA addımı sessiyaya bağlıdır və qərar dil grant-ı, target, revision və gözlənən versiya ilə serverdə yoxlanır; audit qərarı, səbəbi və versiya hash-lərini saxlayır, mətn və sirri saxlamır |
| MOD-02 | Etirafdan və rəydən şikayət, təkrar və etiraz | Hədəf və kanal üzrə ayrı qruplar, eyni reporter üçün qrup başına tək göndəriş, server hesablı təcili/hüquqi növbə, şifrəli izah, kütləvi şikayətin avtomatik gizlətməməsi, dil grant-ı və təzə MFA, atomik auditli dismiss/hide, hər hide auditinə yalnız sahibin bir etirazı və uyğun olduqda başqa moderatorun qərarı; bərpa mövcud visibility/outbox yolundan keçir |
| MOD-03 | Rəyin gizlədilməsi, davam və bağlama | Yalnız təsdiqlənmiş uşaq revision görünür; rəy gizlədiləndə onun müəllif cavabı da gizlənir, etiraf qalır; təsdiqli davam bir mətnsiz outbox hadisəsi yaradır; qapalı rəy axını köhnə təsdiqləri saxlayır və yeni göndərişi rədd edir; detail/feed/fragment/sitemap-də hidden/pending uşaq mətni yoxdur |
| SEO-01 | Hər dil üzrə yayımdan dərhal sonra | 200, ilkin HTML, canonical, məzmun üçün daxili link və düzgün sitemap, dil dəyişdirmə yoxdur |
| SEO-02 | Redaktə, geri çəkmə və daimi silmə | Real dəyişiklik lastmod-u yeniləyir, geri çəkilən sitemap-dən çıxır, daimi silinən URL 410, preview və keş təmiz |
| SEO-03 | IndexNow və Search Console/Bing yoxlaması | Təkrar cəhd, düzgün URL dəyişiklikləri, həqiqi xarici yoxlama sübutu, domen profili düzgün |
| ADV-01 | Consent yoxdur/var, az dili, uyğun olmayan rəy | Uyğunluq dəyişəndə ads kodu çıxarılır, az və şəxsi ekranlarda reklam yoxdur, qlobal söndürmə işləyir |
| ADV-02 | GA4 şəbəkə izi, razılıq və hadisə | Razılıqdan əvvəl GA sorğusu yoxdur. Sonra açıq mətn, title, xam URL/referrer, etiraf ID-si və sahiblik məlumatı sızmır |
| PWA-01 | Quraşdırma, köhnə keş, oflayn və push | Yalnız statik keş, açıq yazı saxlanır, oflayn göndəriş baş vermir, bildiriş neytraldır |
| OPS-01 | Yüklə, cron dayanması və qayıtması | Açıq oxu və yazı işləyir, cron üst-üstə düşmür, outbox təkrar göndərişdən qorunur |
| OPS-02 | Silmədən əvvəlki backup, bərpa və reklamlı sürət | Silinmiş mətn geri açılmır, rollback sənədlidir, reklamlı və reklamsız mobil performans ayrıca qeydə alınır |
| DOC-01 | Sənədləşmə və qəsdən səhv edilmiş kontrakt | Təmiz sənədlər keçir, çeşidlənmiş yanlış dil və kateqoriya, naməlum qəbul ID və qırılmış placeholder rədd edilir |
| UI-04 | Mobil və masaüstü atlas müqayisəsi | Əsas ekranlar və qırılma vəziyyətləri atlasda var, wireframe-a uyğun gündüz/gecə, fokus və düymə ardıcıllığı ölçülür |
| LOC-03 | 17 paketdə kateqoriya, görünüş və sahib baxışı | 204 kateqoriya etiketi və bütün paket açarları yoxlanır, hər locale nüsxəsi vizual sınaq və baxış qeydini alır |
| OWN-03 | Eyni etiraf üzrə paralel bərpa və hesab transferi | Eyni köhnə bərpa kodu ilə yarışda bir qalib olur, köhnə sirr ləğv olunur, əlaqəsiz etiraf və rəyə giriş verilmir |
| MOD-04 | Geri çəkmə, köhnə təsdiq və yeni rəy yarışı | MySQL tranzaksiya sınağında köhnə yol 409 alır və məzmun yenidən açılmır. Təcili gizlətmədə lent, fragment, preview və XML aktualdır; moderatorun köhnə revision/version qərarı, paralel geriçəkmə və paralel dərcdə yalnız cari expected version qalib gəlir, audit/outbox/görünürlük atomikdir və public revision heç vaxt pending mətnə keçmir |
| SEO-04 | Dərc və silmə zamanı nömrəli HTML/cursor | Canonical HTML adi linklə işləyir. Aktiv cursor yeni qeydi qəbul etmir, silinəni keçib düzgün davam edir, vaxtı bitmiş cursor 410 və yeniləmə çağırışı verir |
| ADV-03 | Rəy səthinə keçid, account və oflayn | Ayrı form və şəxsi səthdə reklam və GA4 sorğusu yoxdur. Bildiriş e-poçtu ayrıca opt-in tələb edir və məzmun/alias/şikayət/sahiblik sirri daşımır; tətbiq və cache heç bir sirr və ya qaralama daşımır |
| OPS-03 | Faktiki buraxılış qapıları | Hüquqi operator, hosting/region, 17 dil moderatoru və baxışı, xarici hesablar, backup və səlahiyyətli buraxılış qeydləri bağlıdır |
| CFG-01 | Məhsul limiti konfiqurasiyadan dəyişdirilir | Forma və server validasiyası eyni mərkəzi dəyəri istifadə edir; dəyişdirilə bilən limit, pagination və rate-limit mənbəyi audit sənədində göstərilir; sabit protokol literal-ları əsaslandırılır |
| DISC-01 | Dəstəklənən etiraflar və tövsiyələr | Başlıqdakı ürək cari ziyarətçinin dəstəklədiyi açıq etirafları bütün dillərdə göstərir; siyahı private/no-store/noindex olur. Etiraf detalında cari məzmunu çıxarıb yalnız eyni dil və kateqoriyadan olan ən çox üç açıq tövsiyə göstərilir |
| OWN-04 | Məcburi başlıq və açıq müəllif adı | Başlıqsız etiraf serverdə rədd edilir. Müəllif ad-soyadı istəyə bağlıdır, ayrıca revision-da saxlanır və yalnız həmin revision təsdiqlənəndə ictimai göstərilir; ad boşdursa etirafdaxili anonim alias qalır |
| UI-05 | Standart hüquqi səhifələrə keçid | İstifadə şərtləri, icma qaydaları və məxfilik bildirişi ortaq footer-dən əlçatandır və cari dildə açılır |
| UI-06 | Etiraf şikayətləri modalda açılır | İcma şikayəti və hüquqi bildiriş keçidləri mövcud formanı əlçatan popup-da açır; bağlama düyməsi, backdrop və Escape ilə qapanır, fokus trigger-ə qayıdır, JavaScript olmayanda linklər ayrıca forma səhifəsini açır |
| UI-07 | Şikayət popup-ları mobil və masaüstündə uyğunlaşır | Dar ekranlarda dialog viewport və safe-area daxilində qalır, uzun məzmun daxildə skrollanır, bağlama nəzarəti görünən qalır; forma eninə daşmır və düymələr toxunmağa rahatdır |

Test nəticəsini PR-a əlavə et: mühit, əmr və nəticə. Avtomatik test, browser sınağı, manual baxış və xarici hesab doğrulamasını ayrıca qeyd et. 75-ci persentil üçün mobil sahə hədəfləri LCP ≤2,5 s, INP ≤200 ms, CLS ≤0,1. Trafik olmayanda lab nəticəsini sahə ölçümü kimi etiketləmə.
