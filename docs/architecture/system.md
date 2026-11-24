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

- İctimai etiraf ID-si, səkkiz simvolluq `Lavbe #XXXXXXXX` müəllif kodu və bərpa sirri bir-birindən asılı deyil. Hər etirafın müəllif kodu bazada bütün etiraflar üzrə unikaldır; nadir toqquşmada yeni dəyər yaradılır. Eyni hesabın etirafları ayrı kod alır və sahiblik koddan çıxarılmır. Rəy iştirakçısının aliası ayrıca iştirak qaydasıdır.
- Sessiya və qonaq identifikatoru HTTPS, Secure, HttpOnly, SameSite və CSRF qoruması ilə işləyir. Production-da session cookie Secure olur; HTTP development mühitində `SESSION_SECURE_COOKIE` verilmədən lokal istifadə mümkündür. Cihaz yaddaşı istəyə bağlıdır. Qonağın əməliyyat hüququ serverdə yoxlanır. Məxfi bərpa açarı form vasitəsilə ötürülür, heşlə saxlanır, istifadədən sonra rotasiya edilir və həmin etirafın əvvəlki qonaq idarəetmə icazəsi ləğv olunur.
- Hesaba keçid açıq məzmunu ictimai hesabla bağlamır. İctimai müəllif serializasiyasında e-poçt, hesab ID-si, qonaq açarı və başqa etiraf əlaqəsi yoxdur.
- Göndəriş, rəy, reaksiya, şikayət və moderator qərarında təkrar sorğular təhlükəsizdir. Müştəri idempotent açar göndərir, server təkrar əməliyyatı bir nəticəyə bağlayır. Köhnə moderator ekranından yeni versiya üzərində qərar verilə bilməz.
- Təsdiq tranzaksiyasında açıq versiya, sitemap üçün mənbə vəziyyəti və outbox hadisəsi birlikdə yazılır. Oxu və sitemap cari committed vəziyyətdən hesablana bilir. Xarici göndəriş yayımı bloklamır.

## Oxu, keş və cron

Nömrəli açıq HTML və XML cari ictimai görünürlük qaydasını tətbiq edir. Açarlar locale, səhifə növü, səhifə, kateqoriya və məzmun versiyasını əhatə edir. Şəxsi səhifə və forma cavabları, həmçinin iştirakçıya görə dəyişən etiraf detail-i `Cache-Control: private, no-store` alır. Razılıq və şəxsi məlumat ümumi keşlə qarışmır. Geri çəkmə və silmədən sonra yeni sorğu cari committed görünürlük vəziyyətini yoxlayır; əvvəlki məzmun kənar keşdən xidmət edilmir.

Hazırkı scheduler bildiriş və IndexNow əmrlərini hər dəqiqə, keş və oxucu heşi təmizliyini gündəlik qısa paketlərlə planlayır. Hosting cron-u hər dəqiqə `php artisan schedule:run` çağırmalıdır; faktiki hosting qabiliyyəti buraxılış qapısında yoxlanır. Kilid üst-üstə icranı dayandırır. Outbox tapşırıqları təkrar icraya davamlıdır, bağlantı timeout-u, artan gözləmə və xəta növbəsi var. Oxu, göndəriş, şikayət və sitemap cron dayansa da işləyir. Admin/əməliyyat yoxlaması işin yaşını və son uğurlu icranı göstərir.

## PWA

HTTPS, manifest, 192/512 ölçülü app ikonları, maskable variant, Apple touch icon və standalone giriş üçün statik PWA qabığı var. Quraşdırma dəstəklənən brauzerdə Mənim bölməsindən başlayır; real iOS/Android və staging yoxlaması qalır. Service worker yalnız versiyalı statik aktivləri və ümumi, dillərə uyğun oflayn görünüşü saxlayır; ictimai etiraf HTML-i, rəy, admin, auth, bərpa və qaralama onun keşinə düşmür. Açıq redaktor yalnız oxucu cihazda saxlama seçimini etdikdə başlıq və mətni yeddi gün brauzerin yerli yaddaşında saxlayır; oflayn göndəriş serverə qayıdanda istifadəçi tərəfindən edilir. Web Push abunəsi və çatdırılması LAV-011-in açıq işidir; hazır PWA qabığı push xidmətinin işlədiyini göstərmir.
