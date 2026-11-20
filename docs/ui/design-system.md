# Dizayn sistemi

Vahid Blade komponentləri ictimai sayt, hesab, admin, hüquqi və xəta səhifələrində tətbiq olunur. Mərkəzi CSS dəyişənləri rəng, boşluq, tipoqrafiya, toxunma sahəsi, fokus və keçid vaxtlarını idarə edir. Dil mətnləri komponentə parametr kimi gəlir.

## Lavbe wordmark-ı

Header-də istifadəçinin verdiyi lələk və “Lavbe” söz nişanı birlikdə tam vektor SVG kimi göstərilir. Əsas rəng `--brand-logo-color`, çəhrayı ürək vurğusu `--brand-accent-color` CSS dəyişənidir; gündüz/gecə rejimi uyğun rəng seçir və hər ikisi istənilən CSS rəngi ilə əvəz oluna bilər. Lələyin eyni konturu ayrıca `public/icons/lavbe-feather.svg` favicon-udur; raster ehtiyatı da həmin SVG-dən yaradılır. SVG-lərdə base64 və ya raster şəkil yoxdur.

## Rəng və rejim

| Rol | Gündüz | Gecə |
|---|---|---|
| Fon | `#F7F3EB` | `#211D19` |
| Səth | `#FFFDF8` | `#2B2520` |
| Əsas mətn | `#352C26` | `#F5ECE0` |
| Köməkçi mətn | `#716257` | `#CBBBA9` |
| Əsas düymə və yazısı | `#79513A` / `#FFFFFF` | `#E6B68E` / `#302117` |
| Aktiv sahənin sərhədi | `#A18B79` | `#A28B78` |

Sistem rejimi ilkin seçimdir. Gündüz və Gecə brauzerdə yadda qalır. İlk görüntüdə uyğun rəng tətbiq edilir. Mətn kontrastı WCAG AA üzrə yoxlanır. Fokus göstəricisi və statusun yazısı rəngdən ayrıca görünür. Sistem şriftləri yerli yazı sisteminə uyğun seçilir. Mətn sahəsi 17–18 px, əsas toxunma sahələri ən az 44 × 44 px, oxu sütunu masaüstündə maksimum 42 rem. Kiçik ekranın minimum sınaq eni 320 CSS px-dir.

## Komponentlər

- Başlıq, mobil Oxu/Yaz/Mənim naviqasiyası, masaüstü naviqasiya, düymə, sahə, dialoq, mövzu seçimi, status, bildiriş, kart, skelet, səhifələmə və oflayn ekran.
- Kart hiyerarxiyası: mövzu, unikallaşdırılmış başlıq, təsdiqlənmiş mətndən qısa parça, lazım olduqda Davamı gəldi. Skeleton eyni kart komponentinin geometriyasından istifadə edir.
- 12 kateqoriyanın lokallaşdırılmış adı lentdə, yazı formasındakı açıla bilən mövzu bələdçisi isə adla yanaşı sərhəd izahını göstərir. Kateqoriyanın sabit açarları [lokalizasiya xəritəsində](../localization.md#kateqoriyaların-sabit-xəritəsi) saxlanır.
- Lucide ikonları yerli SVG dəsti kimi verilir. Yalnız istifadə olunan ikonları saxla, dəstin lisenziya bildirişlərini əlavə et. Etiketli ikonlar dekorativ sayılır, tək ikonlu düymənin əlçatan adı var. Bayraqlar eyni ölçüdə yerli yumru SVG-lərdir, dil etiketi yanında qalır.
- Aşağıdan açılan panellər fokusun geri qaytarılmasını, Escape ilə bağlanmanı və klaviatura idarəsini dəstəkləyir. Təbii düymə və link semantikası qorunur. Azaldılmış hərəkətdə skelet və keçidlər sabitdir.
- Görünüş və hərəkət istifadəçini həssas detal yazmağa sövq etmir. Reklam sahəsi heç bir sistem düyməsinə bənzəmir.

Performans büdcəsi ilkin mobil oxu səhifəsi üçün sıxılmış birinci tərəf CSS ≤35 KB, JS ≤25 KB. Reklam və razılıq skriptləri ayrıca ölçülür.

İstinadlar: [Lucide lisenziyası](https://lucide.dev/license) · [WCAG 2.2](https://www.w3.org/TR/WCAG22/) · [rəng və mədəniyyət araşdırması](https://doi.org/10.1177/0956797620948810)

Oxu lentində axtarış başlığın yanında, mövzular və sıralama isə kartlardan əvvəl yerləşir. Boş lentdən yazı formasına birbaşa keçid var. Dil paneli açılarkən fokus axtarışa keçir. Escape paneli bağlayır və fokusu dil seçiminə qaytarır.

Ortaq naviqasiya etiketləri, dil axtarışı, əsas məzmuna keçid və rejim düyməsinin əlçatan adları 17 dildə navigation.php mənbəyindən alınır. Rejim dəyişəndə JavaScript əlçatan adı həmin dildə yeniləyir.

Lokal məzmun baxışı üçün `php artisan db:seed --class=AzerbaijaniDemoConfessionSeeder` əmri sabit 12 kateqoriyanın hərəsinə ən azı iki Azərbaycan dilində uydurma etiraf əlavə edir. Köhnə yas hekayələri yeni `life-changes` mövzusunda qalır; həmin kateqoriyada dörd uyğun nümunə olur. Valideynlik üçün iki yeni hekayə əlavə edilir. Əmr yalnız local mühitdə işləyir, təkrar icra dublikat yaratmır. Hər nümunənin açıq pointer-i öz approved revision-ına bağlanır. Seeder ayrıca çağırılır və `DatabaseSeeder`-ə qoşulmur.

Açıq etiraf səhifəsi lent kartları ilə eyni səth, sərhəd, radius və kölgə qaydasını paylaşır. Başlıq iri sans-serif, müəllif və tarix köməkçi metadata sətrindədir. Şikayət keçidləri mətn kartının sonunda, dəstək və izləmə əməliyyatları aşağıdakı ortaq sırada yerləşir. Rəy və davam formaları ortaq sahə, checkbox və düymə üslubundan istifadə edir.

Ortaq palitra krem fon, isti qəhvəyi vurğu və ağ kart səthlərindən qurulur. Lent və detail başlıqları eyni sans-serif ailəsindədir. Lent kartında kateqoriya və tarix yuxarıda, anonim ad aşağıda görünür.

Dil seçiminin hər sətrində flag-icons dəstindən yerli SVG bayraq, native ad və onun altında ingiliscə ad göstərilir. Seçilmiş dil işarələnir. Dəstin mənbə commit-i və MIT lisenziyası public/icons/flags qovluğunda saxlanır.

Hesab formalarında hər etiket konkret input ID-sinə bağlıdır. Validasiya xətası sahənin altında göstərilir və aria-describedby ilə həmin sahəyə bağlanır. Checkbox elementləri adi mətn inputunun tam en və minimum hündürlük qaydasından ayrıca ölçülənir.

Eyni etiket və xəta əlaqələri yazı, şikayət, etiraz, bərpa və moderasiya formalarında da tətbiq olunur. Moderator icazələrinin ləğvində hər səbəb sahəsinin ID-si grant ID-sinə görə unikaldır. HTML sınaqları ictimai formalarda etiket hədəflərini, təkrarlanan ID-ləri və aria-describedby istinadlarını yoxlayır.

404, dialoq, yüklənmə və footer mətnləri də cari locale ilə render olunur. Bu UI mətnləri LAV-010 native-copy mənbəyindəki 17 dil paketindən götürülüb. Məhsul sahibinin yekun dil baxışı LAV-010-da izlənir.
