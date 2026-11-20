# Məhsul spesifikasiyası

## Məqsəd və sərhəd

Lavbe anonim, mətn əsaslı etiraf və dəstək platformasıdır. Açılışda 17 dil, 12 kateqoriya, oxu lenti, iştirak, öz məzmununu idarəetmə, moderasiya, PWA, SEO və AdSense infrastrukturu işləkdir. AdSense yeganə monetizasiyadır. Məhsula şəkil yükləmə, şəxsi mesaj, ictimai hesab profili, reytinqli istifadəçi cədvəli və ödənişli hesab daxil edilmir.

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

Lavbe wordmark-ı lisenziyalı Lucide lələk ikonu və Caveat əl yazısı şrifti ilə göstərilir. Eyni lələk SVG-si favicon-dur; şrift repo daxilində lokal host olunur.

| Ekran | Əsas əməliyyat və nəticə |
|---|---|
| Dil lenti və kateqoriya | 12 etirafdan ibarət səhifələri nömrə ilə dəyiş, mövzu dəyiş, seçilmiş dilin içində kəşf et |
| Etiraf | Tam mətn, anonim müəllif, təsdiqlənmiş davam və rəylər; səhifənin altında rəy yaz, rəylərə reaksiya ver və konkret rəyə birbaşa cavab yaz; paylaş, izlə, dəstək, popup-da icma şikayəti və hüquqi bildiriş |
| Yaz və redaktə | Məcburi başlıq, mətn və istəyə bağlı kateqoriya, istəyə bağlı cavab gözləntisi və yeni rəylərə icazə. Göndərişin dili görünür. |
| Göndəriş təsdiqi | Baxış statusu, cihaz seçimi, məxfi bərpa kodu. Paylaşım URL-si yayımdan sonra. |
| Mənim | Bağlanmış Etiraflarım və Rəylərim, İzlədiklərim, Bildirişlər, tətbiqdaxili/e-poçt seçimi, giriş və parol bərpası |
| Şikayət | Etiraf və ya rəy hədəfi, səbəb, istəyə bağlı izah, qəbul təsdiqi |
| Admin | Etiraflar, Rəylər, Şikayətlər. Dil və status filtri. Kartdan qərar. |
| Kömək və hüquqi | Qaydalar, məxfilik, seçimlər, qanunsuz məzmun, əlaqə, əlçatan dil keçidi |

## Əsas qaydalar

- Etiraf və rəy göndərişi 18+ məhsul siyasətidir. Açıq oxu saxlanır. Bazar üzrə tətbiq üsulunu [buraxılış](delivery/operations.md) müəyyən edir.
- Etiraf mövzusu boşdursa moderator təyin edir. Bir əsas kateqoriya seçilir. Cavab gözləntisi istəyə bağlıdır: sadəcə bölüşmək, bənzər təcrübə eşitmək və ya fikir almaq.
- Mətn sadə Unicode mətnidir. Mətn limitlərinin işlək mənbəyi `config/lavbe.php`-dəki `limits` bölməsidir; forma və server yoxlamaları eyni dəyərləri oxuyur. Başlanğıc maksimumları etiraf və davam üçün 6 000, rəy üçün 1 200, başlıq üçün 120 simvoldur. UTF-8 və emoji qəbul olunur. Sintetik SEO söz minimumu yoxdur. Xarici URL mətn kimi görünür.
- İstəyə bağlı müəllif ad-soyadı ayrıca revision sahəsində saxlanır və yalnız həmin revision təsdiqlənəndə açıq görünür. İstifadəçi ad yazmasa etiraf çərçivəsində sabit `Lavbe #XXXXXXXX` formatındadır. Müəllifə Müəllif nişanı verilir. Başqa etirafda fərqli açıq ad verilir. İctimai profil və müəllifin digər etirafları siyahısı yaradılmır.
- Rəy yazma sahəsi etiraf səhifəsinin altında görünür. Rəy və ona birbaşa cavab eyni moderasiya, sahiblik və bərpa qaydalarını keçir; cavab daha dərin nested thread yarada bilməz. Qonaq hər açıq rəyə sessiya üzrə bir reaksiya verib geri götürə bilər. Əsas rəy gizlədiləndə ona verilən cavablar da gizlənir; əsas rəy bərpa olunsa belə cavablar ayrıca moderasiya qərarı olmadan açılmır. Yeni rəyləri bağlamaq əvvəlki təsdiqlənmiş rəyləri gizlətmir. Şikayət ayrı işləyir. Müəllifin davamı eyni etiraf URL-sində tarixlə göstərilir.
- Lent sıralaması Yeni, Bu həftə və Məşhur etiraflardır. Məşhur sırası bütün müddət üzrə aktiv dəstəyə 1, təsdiqlənmiş unikal açıq rəyçiyə 2 xal verir; bərabərlik yayım vaxtı və ID ilə sabitlənir. Etirafın davamları ayrıca detail səhifəsində qalır.
- Dəstək reaksiyası etiraf üzrə sessiya iştirakçısının bir aktiv seçimidir və geri götürülə bilər. Yalnız təsdiqlənmiş, canlı etirafda işləyir; say birbaşa SQL artım/azalması ilə qorunur. Təsdiqlənən rəylər 15 dəqiqəlik qrup açarı ilə bildiriş outbox-unda birləşdirilir. Təsdiqlənən davam üçün bir outbox hadisəsi yaranır. Account follow bildirişləri outbox-dan qısa cron əmri ilə yaradılır. Yükdə mətn yoxdur; in-app saxlanması və e-poçt çatdırılması hesaba bağlıdır, e-poçt ayrıca opt-in tələb edir. Web Push LAV-011-dədir.
- Rəylər ayrıca dəyişməz revision-da saxlanır, moderasiya təsdiq edənədək açıq səthlərdən gizlidir. Müəllif rəyə yalnız bir cavab verə bilər; cavabın özü də təsdiq tələb edir. Rəy sahibi onu cihaz sessiyası, istəyə bağlı HttpOnly cookie və ya birdəfəlik rotasiya olunan bərpa kodu ilə idarə edir. Bərpa kodunun yalnız HMAC heşi saxlanır. Müəllifin davamı əsas etiraf sahiblik sübutu ilə göndərilir və moderasiya təsdiq edənədək açıq deyil.
- İctimai detail səhifəsi iştirakçı sessiyasına görə formaları və dəstək vəziyyətini dəyişdirdiyindən `private, no-store` qaytarır. Açıq etiraf məzmunu yenə ilkin HTML-də mövcuddur; sitemap və indekslənmə göstərişi ayrıca qalır.
- Artan hər istifadəçi siyahısı məhdud ölçülü, nömrəli səhifələrə bölünür. Əvvəlki/növbəti və yaxın səhifə nömrələri adi HTML linki və ya POST formu kimi işləyir; eyni ekrandakı siyahıların səhifələri bir-birindən asılı deyil. Axtarış sözü POST gövdəsində qalır, hesab və dəstəklənənlər siyahısı private/no-store olur. Tövsiyə və cavab önbaxışı kimi sabit saylı bloklar ayrıca səhifələnmir.
- Mövcud qaralama dil dəyişərkən qorunur. Redaktə təsdiqlənənədək əvvəlki açıq versiya yaşayır. Geri çəkmə dərhal gizlədir. Daimi silinmə ayrıca təsdiqlənir.
