# Qəbul matrisi

| ID | Ssenari | Gözlənən nəticə |
|---|---|---|
| BAS-01 | Təmiz checkout və MySQL ailəsi ilə CI | Asılılıqlar lock faylından qurulur, smoke test və sənəd yoxlaması keçir, prod mühit açarı tələb olunmur |
| UI-01 | Ana, mövzu, 404 və admin | Ortaq komponentlər, Lucide, fokus, gündüz/gecə və sabit mobil quruluş |
| UI-02 | Rusca Ailə lenti, şəbəkə kəsilməsi və təkrar | HTML-də rusca kartlar, skelet eyni ölçüdə, köhnə kartlar qorunur, təkrar ID yoxdur, JS olmadan HTML səhifələmə |
| UI-03 | 320 px, iPhone Safari, Android Chrome, standalone | Klaviatura kursoru örtmür, mətni xəta silmir, geri keçid mövqeyi mümkünsə bərpa edir |
| LOC-01 | İlk giriş, əl ilə dil və birbaşa URL | Seçim ardıcıllığı işləyir, seçilmiş dil yadda qalır, konkret URL ölkəyə görə yönləndirilmir, axtarış native və English adları tapır |
| LOC-02 | 17 paket və 12 kateqoriya | Açarlar, placeholder, slug, yazı və hüquqi səhifə mətnləri tamdır, sahibin baxış versiyası ayrıdır |
| OWN-01 | Qonaq etirafı, kod bərpası və başqa cihaz | Dublikat yaranmır, alias ayrıdır, saxta sahiblik rədd edilir, bərpa köhnə icazəni dayandırır |
| OWN-02 | İstəyə bağlı hesab, bildiriş və rəy sahibliyi | Müvafiq icazələr qorunur, şəxsi cavab cache edilmir, həssas payload xaricdə görünmür |
| MOD-01 | İlk yayım, redaktə, geri çəkmə və rədd | Yalnız təsdiqlənmiş versiya görünür, köhnə moderator qərarı yeni versiyaya tətbiq edilmir |
| MOD-02 | Etirafdan və rəydən şikayət, təkrar və etiraz | Ayrı hədəflər, birləşmiş iş, tək təkrar və səbəbli səlahiyyətli qərar |
| MOD-03 | Rəyin gizlədilməsi, davam və bağlama | Rəy ayrıca çıxır, etiraf qalır, davam bir bildiriş yaradır, qapalı rəy axını köhnə rəyləri saxlayır |
| SEO-01 | Hər dil üzrə yayımdan dərhal sonra | 200, ilkin HTML, canonical, məzmun üçün daxili link və düzgün sitemap, dil dəyişdirmə yoxdur |
| SEO-02 | Redaktə, geri çəkmə və daimi silmə | Real dəyişiklik lastmod-u yeniləyir, geri çəkilən sitemap-dən çıxır, daimi silinən URL 410, preview və keş təmiz |
| SEO-03 | IndexNow və Search Console/Bing yoxlaması | Təkrar cəhd, düzgün URL dəyişiklikləri, həqiqi xarici yoxlama sübutu, domen profili düzgün |
| ADV-01 | Consent yoxdur/var, az dili, uyğun olmayan rəy | Uyğunluq dəyişəndə ads kodu çıxarılır, az və şəxsi ekranlarda reklam yoxdur, qlobal söndürmə işləyir |
| ADV-02 | GA4 şəbəkə izi, razılıq və hadisə | Razılıqdan əvvəl GA sorğusu yoxdur. Sonra açıq mətn, title, xam URL/referrer, etiraf ID-si və sahiblik məlumatı sızmır |
| PWA-01 | Quraşdırma, köhnə keş, oflayn və push | Yalnız statik keş, açıq yazı saxlanır, oflayn göndəriş baş vermir, bildiriş neytraldır |
| OPS-01 | Yüklə, cron dayanması və qayıtması | Açıq oxu və yazı işləyir, cron üst-üstə düşmür, outbox təkrar göndərişdən qorunur |
| OPS-02 | Silmədən əvvəlki backup, bərpa və reklamlı sürət | Silinmiş mətn geri açılmır, rollback sənədlidir, reklamlı və reklamsız mobil performans ayrıca qeydə alınır |

Test nəticəsini PR-a əlavə et: mühit, əmr və nəticə. Avtomatik test, browser sınağı, manual baxış və xarici hesab doğrulamasını ayrıca qeyd et. 75-ci persentil üçün mobil sahə hədəfləri LCP ≤2,5 s, INP ≤200 ms, CLS ≤0,1. Trafik olmayanda lab nəticəsini sahə ölçümü kimi etiketləmə.
