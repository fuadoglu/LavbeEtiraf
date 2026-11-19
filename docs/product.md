# Məhsul spesifikasiyası

## Məqsəd və sərhəd

Lavbe anonim, mətn əsaslı etiraf və dəstək platformasıdır. Açılışda 17 dil, 12 kateqoriya, oxu lenti, iştirak, öz məzmununu idarəetmə, moderasiya, PWA, SEO və AdSense infrastrukturu işləkdir. AdSense yeganə monetizasiyadır. Məhsula şəkil yükləmə, şəxsi mesaj, ictimai hesab profili, reytinqli istifadəçi cədvəli və ödənişli hesab daxil edilmir.

## Rollar

| Rol | Əməliyyatlar |
|---|---|
| Oxucu | Açıq lent və etiraf, mövzu seçimi, dəstək reaksiyası, izləmə, paylaşma, şikayət |
| Qonaq müəllif və rəyçi | Qeydiyyatsız yazı, cihaz və ya məxfi bərpa vasitəsilə öz məzmununu idarə etmək |
| Hesab sahibi | E-poçt ilə təsdiqlənmiş giriş, öz etiraf və rəylərini uyğun sübutla hesabına bağlamaq, cihazlararası idarəetmə |
| Moderator | Səlahiyyətli dillərdə aidiyyəti materialı və şikayəti görmək, səbəbli qərar vermək |
| Admin | Moderatorun dil icazələri və əməliyyat konfiqurasiyası, eyni üç bölməli panel |

## Ekran xəritəsi və davranış

| Ekran | Əsas əməliyyat və nəticə |
|---|---|
| Dil lenti və kateqoriya | Oxu, mövzu dəyiş, daha çox yüklə, seçilmiş dilin içində kəşf et |
| Etiraf | Tam mətn, anonim müəllif, təsdiqlənmiş davam və rəylər, paylaş, izlə, dəstək, şikayət, növbəti etiraf |
| Yaz və redaktə | Mətn, istəyə bağlı başlıq və kateqoriya, istəyə bağlı cavab gözləntisi və yeni rəylərə icazə. Göndərişin dili görünür. |
| Göndəriş təsdiqi | Baxış statusu, cihaz seçimi, məxfi bərpa kodu. Paylaşım URL-si yayımdan sonra. |
| Mənim | Etiraflarım, rəylərim, izlədiklərim, bildirişlər, rejim, cihaz seçimi, hesaba giriş və bərpa |
| Şikayət | Etiraf və ya rəy hədəfi, səbəb, istəyə bağlı izah, qəbul təsdiqi |
| Admin | Etiraflar, Rəylər, Şikayətlər. Dil və status filtri. Kartdan qərar. |
| Kömək və hüquqi | Qaydalar, məxfilik, seçimlər, qanunsuz məzmun, əlaqə, əlçatan dil keçidi |

## Əsas qaydalar

- Etiraf və rəy göndərişi 18+ məhsul siyasətidir. Açıq oxu saxlanır. Bazar üzrə tətbiq üsulunu [buraxılış](delivery/operations.md) müəyyən edir.
- Etiraf mövzusu boşdursa moderator təyin edir. Bir əsas kateqoriya seçilir. Cavab gözləntisi istəyə bağlıdır: sadəcə bölüşmək, bənzər təcrübə eşitmək və ya fikir almaq.
- Mətn sadə Unicode mətnidir. Başlanğıc maksimumları etiraf və davam üçün 6 000, rəy üçün 1 200, başlıq üçün 120 simvoldur. UTF-8 və emoji qəbul olunur. Sintetik SEO söz minimumu yoxdur. Xarici URL mətn kimi görünür.
- Açıq iştirakçı adı etiraf çərçivəsində sabit `Lavbe #XXXXXXXX` formatındadır. Müəllifə Müəllif nişanı verilir. Başqa etirafda fərqli açıq ad verilir. İctimai profil və müəllifin digər etirafları siyahısı yaradılmır.
- Rəylər bir səviyyəlidir. Müəllif rəyə bir cavab verə bilər. Yeni rəyləri bağlamaq əvvəlki təsdiqlənmiş rəyləri gizlətmir. Şikayət ayrı işləyir. Müəllifin davamı eyni etiraf URL-sində tarixlə göstərilir.
- Dəstək reaksiyası etiraf üzrə sessiya iştirakçısının bir aktiv seçimidir və geri götürülə bilər. Yalnız təsdiqlənmiş, canlı etirafda işləyir; say birbaşa SQL artım/azalması ilə qorunur. Saytdaxili bildiriş əsasdır. Təsdiqlənən rəylər 15 dəqiqəlik qrup açarı ilə bildiriş outbox-unda birləşdirilir. Təsdiqlənən davam üçün bir outbox hadisəsi yaranır. Outbox yükündə rəy və etiraf mətni saxlanmır; real bildiriş çatdırılması LAV-009-dadır. E-poçt və Web Push müstəqil seçimlərdir.
- Rəylər ayrıca dəyişməz revision-da saxlanır, moderasiya təsdiq edənədək açıq səthlərdən gizlidir. Müəllif rəyə yalnız bir cavab verə bilər; cavabın özü də təsdiq tələb edir. Rəy sahibi onu cihaz sessiyası, istəyə bağlı HttpOnly cookie və ya birdəfəlik rotasiya olunan bərpa kodu ilə idarə edir. Bərpa kodunun yalnız HMAC heşi saxlanır. Müəllifin davamı əsas etiraf sahiblik sübutu ilə göndərilir və moderasiya təsdiq edənədək açıq deyil.
- İctimai detail səhifəsi iştirakçı sessiyasına görə formaları və dəstək vəziyyətini dəyişdirdiyindən `private, no-store` qaytarır. Açıq etiraf məzmunu yenə ilkin HTML-də mövcuddur; sitemap və indekslənmə göstərişi ayrıca qalır.
- Mövcud qaralama dil dəyişərkən qorunur. Redaktə təsdiqlənənədək əvvəlki açıq versiya yaşayır. Geri çəkmə dərhal gizlədir. Daimi silinmə ayrıca təsdiqlənir.
