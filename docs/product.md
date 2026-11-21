# Məhsul spesifikasiyası

## Məqsəd və sərhəd

Lavbe anonim, mətn əsaslı etiraf və dəstək platformasıdır. Açılışda 17 dil, 12 kateqoriya, oxu lenti, iştirak, öz məzmununu idarəetmə, moderasiya və SEO infrastrukturu işləkdir; locale manifestli, həssas HTML-i offline saxlamayan PWA qabığı tətbiq olunur, cihazda quraşdırma sınağı isə buraxılış qapısıdır. Reklam inteqrasiyası xarici buraxılış qapısıdır. AdSense yeganə monetizasiyadır. Məhsula şəkil yükləmə, şəxsi mesaj, ictimai hesab profili, reytinqli istifadəçi cədvəli və ödənişli hesab daxil edilmir.

## Rollar

| Rol | Əməliyyatlar |
|---|---|
| Oxucu | Açıq lent və etiraf, mövzu seçimi, dəstək reaksiyası, izləmə, paylaşma, şikayət |
| Qonaq müəllif və rəyçi | Qeydiyyatsız yazı, cihaz və ya məxfi bərpa vasitəsilə öz məzmununu idarə etmək |
| Hesab sahibi | E-poçt ilə təsdiqlənmiş giriş, öz etiraf və rəylərini uyğun sübutla hesabına bağlamaq, cihazlararası idarəetmə, follow və bildiriş seçimi |
| Moderator | Səlahiyyətli dillərdə aidiyyəti materialı və şikayəti görmək, səbəbli qərar vermək |
| Admin | Moderatorun dil icazələri və əməliyyat konfiqurasiyası, eyni üç bölməli panel |

## Ekran xəritəsi və davranış

Header dil seçicisi desktop-da cari locale-in bayrağını və tam native adını göstərir, locale kodunu göstərmir; mobil ölçüdə yalnız bayraq görünür, native ad screen reader-lər üçün əlçatan qalır. Açılan menyuda native və ingiliscə adlar saxlanır.

Header-də istifadəçinin təqdim etdiyi tam vektor logo göstərilir. Onun lələk konturu ayrıca SVG favicon-u və ICO fallback-u kimi işləyir; logo rəngləri CSS dəyişənləri ilə dəyişdirilir.

| Ekran | Əsas əməliyyat və nəticə |
|---|---|
| Dil lenti və kateqoriya | Dolu lentdə etiraflar erkən görünür; mövzunu açılan filtrdən seç, desktop-da yan siyahıdan keç. 12 etiraflıq səhifələri nömrə ilə dəyiş, hər kateqoriyanın unikal ikonunu gör və seçilmiş dildə kəşf et |
| Etiraf | Tam mətn, anonim müəllif, unikal oxunuş sayı, təsdiqlənmiş davam və rəylər; səhifənin altında rəy yaz, rəylərə reaksiya ver və konkret rəyə birbaşa cavab yaz; eyni dildə, başqa kateqoriyalardan təsadüfi seçilmiş ən çox üç fərqli etirafın yalnız başlıqlarını tövsiyə kimi göstər; etirafı paylaş, izlə, dəstək ol; bu üç əməliyyat detail səhifəsində yan-yana düymələrdir; popup-da icma şikayəti və hüquqi bildiriş. Etiraf öz kateqoriyasının favicon-unu göstərir. |
| Yaz və redaktə | Əvvəlcə hekayə mətni üçün sahə görünür. Başlıq məcburidir; kateqoriya, cavab gözləntisi və yeni rəylərə icazə istəyə bağlıdır. Adsız paylaşma imkanı əvvəlcədən aydın bildirilir, müəllif istəsə açıq adını əlavə edir. Kateqoriya seçimi bütün 17 dildə mövzu adını verir və açıla bilən bələdçidə həmin mövzunun sərhədini izah edir. Göndərişin dili görünür. |
| Göndəriş təsdiqi | Baxış statusu, cihaz yaddaşı seçimi, məxfi bərpa kodu. Yazı formasında moderator baxışı, təsdiqdən sonra açıq yayım və axtarış nəticələrində görünmə ehtimalı əvvəlcədən bildirilir. Paylaşım URL-si yayımdan sonra. |
| Mənim | Qonaq üçün həmin cihazdakı etirafların başlıq və moderasiya statusunu göstərən ayrıca şəxsi siyahı; hesab sahibi üçün bağlanmış etiraf və rəylər, izlədikləri, bildirişlər, seçimlər və hesab idarəsi. |
| Şikayət | Etiraf və ya rəy hədəfi, səbəb, istəyə bağlı izah, qəbul təsdiqi |
| Admin | Etiraflar, Rəylər, Şikayətlər. Dil və status filtri. Kartdan qərar. |
| Kömək və hüquqi | Footer-dən istifadə şərtləri, icma qaydaları, məxfilik və kuki bildirişləri, qanunsuz məzmun bildirişi, məxfilik sorğusu və operator məlumatı səhifələrinə cari dildə keçid |

## Əsas qaydalar

- Etiraf və rəy göndərişi 18+ məhsul siyasətidir. Etiraf formasında istifadəçi ayrıca təsdiq verir; rəy və cavab formaları yaş təsdiqi checkbox-u tələb etmir. Checkbox yaşın faktiki yoxlanması sayılmır. Açıq oxu saxlanır. Bazar üzrə tətbiq üsulunu [buraxılış](delivery/operations.md) müəyyən edir.
- Etiraf mövzusu boşdursa moderator təyin edir. Bir əsas kateqoriya seçilir. Cavab gözləntisi istəyə bağlıdır: sadəcə bölüşmək, bənzər təcrübə eşitmək və ya fikir almaq.
- Kateqoriya adları və izahları [12 mövzulu xəritəyə](localization.md#kateqoriyaların-sabit-xəritəsi) əsaslanır. Valideynlik `grief` tarixi açarında, yas və yeni başlanğıclar isə `life-changes` açarında saxlanır; köhnə `grief` etirafları data migration ilə yeni uyğun kateqoriyaya keçir.
- Mətn sadə Unicode mətnidir. Mətn limitlərinin işlək mənbəyi `config/lavbe.php`-dəki `limits` bölməsidir; forma və server yoxlamaları eyni dəyərləri oxuyur. Başlanğıc maksimumları etiraf və davam üçün 6 000, rəy üçün 1 200, başlıq üçün 120 simvoldur. UTF-8 və emoji qəbul olunur. Sintetik SEO söz minimumu yoxdur. Xarici URL mətn kimi görünür.
- İstəyə bağlı müəllif ad-soyadı ayrıca revision sahəsində saxlanır və yalnız həmin revision təsdiqlənəndə açıq görünür. İstifadəçi ad yazmasa etiraf çərçivəsində sabit `Lavbe #XXXXXXXX` formatındadır. Müəllifə Müəllif nişanı verilir. Başqa etirafda fərqli açıq ad verilir. İctimai profil və müəllifin digər etirafları siyahısı yaradılmır.
- Rəy yazma sahəsi etiraf səhifəsinin altında görünür. Rəy və ona birbaşa cavab eyni moderasiya, sahiblik və bərpa qaydalarını keçir; cavab daha dərin nested thread yarada bilməz. Qonaq hər açıq rəyə sessiya üzrə bir reaksiya verib geri götürə bilər. Əsas rəy gizlədiləndə ona verilən cavablar da gizlənir; əsas rəy bərpa olunsa belə cavablar ayrıca moderasiya qərarı olmadan açılmır. Yeni rəyləri bağlamaq əvvəlki təsdiqlənmiş rəyləri gizlətmir. Şikayət ayrı işləyir. Müəllifin davamı eyni etiraf URL-sində tarixlə göstərilir.
- Lent sıralaması Yeni, Bu həftə və Məşhur etiraflardır. Məşhur sırası bütün müddət üzrə aktiv dəstəyə 1, təsdiqlənmiş unikal açıq rəyçiyə 2 xal verir; bərabərlik yayım vaxtı və ID ilə sabitlənir. Etirafın davamları ayrıca detail səhifəsində qalır.
- Dəstək reaksiyası etiraf üzrə sessiya iştirakçısının bir aktiv seçimidir və geri götürülə bilər. Yalnız təsdiqlənmiş, canlı etirafda işləyir; say birbaşa SQL artım/azalması ilə qorunur. Təsdiqlənən rəylər 15 dəqiqəlik qrup açarı ilə bildiriş outbox-unda birləşdirilir. Təsdiqlənən davam üçün bir outbox hadisəsi yaranır. Account follow bildirişləri outbox-dan qısa cron əmri ilə yaradılır. Yükdə mətn yoxdur; in-app saxlanması və e-poçt çatdırılması hesaba bağlıdır, e-poçt ayrıca opt-in tələb edir. Web Push LAV-011-dədir.
- Rəylər ayrıca dəyişməz revision-da saxlanır, moderasiya təsdiq edənədək açıq səthlərdən gizlidir. Müəllif rəyə yalnız bir cavab verə bilər; cavabın özü də təsdiq tələb edir. Rəy sahibi onu cihaz sessiyası, istəyə bağlı HttpOnly cookie və ya birdəfəlik rotasiya olunan bərpa kodu ilə idarə edir. Bərpa kodunun yalnız HMAC heşi saxlanır. Müəllifin davamı əsas etiraf sahiblik sübutu ilə göndərilir və moderasiya təsdiq edənədək açıq deyil.
- İctimai detail səhifəsi iştirakçı sessiyasına görə formaları və dəstək vəziyyətini dəyişdirdiyindən `private, no-store` qaytarır. Açıq etiraf məzmunu yenə ilkin HTML-də mövcuddur; sitemap və indekslənmə göstərişi ayrıca qalır.
- Unikal oxunuş sayğacı açıq, təsdiqlənmiş etiraf detail-inə gələn GET sorğusunda işləyir; prefetch/prerender və tanınan crawler/preview user-agent sorğuları sayılmır. Bu, unikal səhifə açılışını ölçür, mətni sona qədər oxumağı təsdiqləmir. Təsadüfi birinci tərəf HttpOnly cookie brauzer profilini tanıyır; hər etiraf üçün ayrıca HMAC heşi və unikal DB məhdudiyyəti eyni profili bir dəfə sayır. Cookie 365 gün qüvvədədir və detail oxunuşunda yenilənir. IP, user-agent, referer və etiraf mətni sayğac üçün saxlanmır. Cookie-ni silmək, bir ildən çox istifadə etməmək, başqa brauzer profili və ya cihaz yeni oxucu kimi görünə bilər. APP_KEY rotasiyası əvvəlki cookie heşləri ilə uyğunluğu bitirə bilər; fiziki cihazı invasiv fingerprinting etmədən müəyyən etmək mümkün deyil.
- Artan hər istifadəçi siyahısı məhdud ölçülü, nömrəli səhifələrə bölünür. Əvvəlki/növbəti və yaxın səhifə nömrələri adi HTML linki və ya POST formu kimi işləyir; eyni ekrandakı siyahıların səhifələri bir-birindən asılı deyil. Axtarış sözü POST gövdəsində qalır, hesab və dəstəklənənlər siyahısı private/no-store olur. Tövsiyə və cavab önbaxışı kimi sabit saylı bloklar ayrıca səhifələnmir.
- İstifadəçi seçərsə, yeni etirafın başlığı və mətni cari brauzer tabında saxlanır və səhifə yenilənəndə və ya dil dəyişəndə qayıdır. Redaktə təsdiqlənənədək əvvəlki açıq versiya yaşayır. Geri çəkmə dərhal gizlədir. Daimi silinmə ayrıca təsdiqlənir.
