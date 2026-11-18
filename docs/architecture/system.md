# Arxitektura və məlumat müqaviləsi

PHP 8.5 üstün, PHP 8.4 uyğun, Laravel 13, Blade, MySQL/MariaDB və az JavaScript. Bir tətbiq, eyni hostda çoxdilli HTML. Sxem istehsal və CI üçün MySQL ailəsində sınaqdan keçir. Mənbə və konfiqurasiya açıq qovluqdan kənardadır. Composer və asset hazırlığı buraxılışda aparılır. İstehsalda Node prosesi, Redis, WebSocket və daimi worker tələb olunmur.

Ətraflı müqavilələr: [sahiblik və nəşr](ownership-and-publication.md), [lent və səhifələmə](feed-contract.md), [trafik və məxfilik](traffic-and-privacy.md).

## Modul sərhədləri

| Modul | Sahib olduğu məlumat və invariant |
|---|---|
| Locale və Taxonomy | Dil reyestri və sabit kateqoriya açarları, lokallaşdırılmış slug |
| Identity və Ownership | Hesab, qonaq açarının heşi, etiraf üzrə bərpa sirrinin heşi, daxili iştirakçı və ictimai alias xəritəsi |
| Content | Confession, immutable revision, public revision pointer, follow-up, comment, müəllif cavabı |
| Moderation | Hədəf və versiya, report group, report submission, qərar, etiraz və audit |
| Engagement | Reaksiya, izləmə, şəxsi bildiriş və kanala razılıq |
| Discovery | Daxili lent, sitemap, keş versiyası, məzmun dəyişiklik hadisələri |
| Operations | Outbox, təkrar cəhd, cron kilidi, health, təmizlik və bərpa qeydləri |

Miqrasiyalar real açarlar və xarici açar məhdudiyyətlərindən istifadə edir. Tətbiq səviyyəsində əlavə olunan bənzər məlumat verilənlər bazasının unikallıq və tranzaksiya qoruması ilə də qorunur. İndekslər lent üzrə `locale, visibility, published_at, id`, kateqoriya üzrə əlavə category, moderasiya üzrə dil, vəziyyət və vaxt kimi faktiki sorğulara uyğun qurulur. Rəy, şikayət və keş auditində sorğu planlarını yoxla. UTC saxla, vaxtı interfeysdə lokallaşdır.

## Yazma və sahiblik

- İctimai etiraf ID-si, iştirakçının etiraf daxilində 8-simvolluq təsadüfi aliası və bərpa sirri bir-birindən asılı deyil. Aliasın unikallığı etiraf üzrə yoxlanır, toqquşmada yeni dəyər yaradılır. Sahiblik aliasdan çıxarılmır.
- Sessiya və qonaq identifikatoru HTTPS, Secure, HttpOnly, SameSite və CSRF qoruması ilə işləyir. Cihaz yaddaşı istəyə bağlıdır. Qonağın əməliyyat hüququ serverdə yoxlanır. Məxfi bərpa açarı form vasitəsilə ötürülür, heşlə saxlanır, istifadədən sonra rotasiya edilir və həmin etirafın əvvəlki qonaq idarəetmə icazəsi ləğv olunur.
- Hesaba keçid açıq məzmunu ictimai hesabla bağlamır. İctimai müəllif serializasiyasında e-poçt, hesab ID-si, qonaq açarı və başqa etiraf əlaqəsi yoxdur.
- Göndəriş, rəy, reaksiya, şikayət və moderator qərarında təkrar sorğular təhlükəsizdir. Müştəri idempotent açar göndərir, server təkrar əməliyyatı bir nəticəyə bağlayır. Köhnə moderator ekranından yeni versiya üzərində qərar verilə bilməz.
- Təsdiq tranzaksiyasında açıq versiya, sitemap üçün mənbə vəziyyəti və outbox hadisəsi birlikdə yazılır. Oxu və sitemap cari committed vəziyyətdən hesablana bilir. Xarici göndəriş yayımı bloklamır.

## Oxu, keş və cron

Açıq HTML, feed fragment və XML üçün eyni görünürlük xidməti işləyir. Açarlar locale, səhifə növü, səhifə, kateqoriya və məzmun versiyasını əhatə edir. Şəxsi səhifə və forma cavabları `Cache-Control: private, no-store` alır. Razılıq və şəxsi məlumat ümumi keşlə qarışmır. Geri çəkmə və silmədən sonra köhnə məzmunun yenidən çıxmasının qarşısını cache generation və müvafiq kənar keşin təmizlənməsi təmin edir. Bunun işləmədiyi hostinq konfiqurasiyasında etiraf HTML-i kənarda keşlənmir.

Cron hostinqin icazə verdiyi intervalda qısa paketlər icra edir. Başlanğıc 5 dəqiqə, təsdiqlənmiş plan dəstəkləmirsə ölçülən alternativ interval. Kilid üst-üstə icranı dayandırır. Outbox tapşırıqları təkrar icraya davamlıdır, bağlantı timeout-u, artan gözləmə və xəta növbəsi var. Oxu, göndəriş, şikayət və sitemap cron dayansa da işləyir. Admin/əməliyyat yoxlaması işin yaşını və son uğurlu icranı göstərir.

## PWA

HTTPS, manifest, 192/512 ölçülü app ikonları, maskable variant, Apple touch icon və standalone giriş. Quraşdırma dəstəklənən brauzerdə Mənim bölməsindən başlayır. Service worker yalnız versiyalı CSS/JS/ikon və dillərdə oflayn görünüşü saxlayır. Etiraf, rəy, admin, auth, bərpa, sitemap, analitika və reklam şəbəkə üzrə işləyir. Yeni oflayn naviqasiya uyğun dilin oflayn ekranını alır. Açıq redaktor oflaynda mətni saxlaya bilir, göndəriş serverə qayıdanda istifadəçi tərəfindən edilir. İstəyə bağlı cihaz qaralaması service worker keşindən ayrıdır. Bildiriş endpoint-ləri məxfi saxlanır, çürük push abunələri təmizlənir, payload yalnız neytral bildiriş və şəxsi bölməyə keçid daşıyır.
