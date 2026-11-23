# Dizayn sistemi

Vahid Blade komponentləri ictimai sayt, hesab, admin, hüquqi və xəta səhifələrində tətbiq olunur. Mərkəzi CSS dəyişənləri rəng, boşluq, tipoqrafiya, toxunma sahəsi, fokus və keçid vaxtlarını idarə edir. Dil mətnləri komponentə parametr kimi gəlir.

## Lavbe wordmark-ı

Header-də istifadəçinin verdiyi lələk və “Lavbe” söz nişanı birlikdə tam vektor SVG kimi göstərilir. Əsas rəng `--brand-logo-color`, çəhrayı ürək vurğusu `--brand-accent-color` CSS dəyişənidir; gündüz/gecə rejimi uyğun rəng seçir və hər ikisi istənilən CSS rəngi ilə əvəz oluna bilər. Lələyin eyni konturu ayrıca `public/icons/lavbe-feather.svg` favicon-udur; raster ehtiyatı da həmin SVG-dən yaradılır. SVG-lərdə base64 və ya raster şəkil yoxdur.

## Rəng və rejim

| Rol | Gündüz | Gecə |
|---|---|---|
| Fon | `#F6E7D8` | `#24191A` |
| Səth | `#FFF4E8` | `#332324` |
| Yuxarı səth | `#FFF9F2` | `#402D2D` |
| Əsas mətn | `#342321` | `#FFEBD8` |
| Köməkçi mətn | `#6D4F45` | `#D6B4A1` |
| Əsas düymə | `#803B48` | `#F1A788` |
| Düymə yazısı | `#FFF8EF` | `#351B1B` |
| Sahə sərhədi | `#9B7062` | `#AA796C` |

Gündüz isti krem və gavalı tonlarına, gecə isə şabalıdı fon və şaftalı vurğularına əsaslanır. Güclü rəng düymə, seçim və fokus kimi kiçik sahələrdə cəmlənir.

- Wilms və Oberfeld 62 iştirakçı ilə rəngin tonu, doyumluluğu və parlaqlığını ayrı ölçüblər. Daha doymuş və parlaq stimullar daha yüksək emosional oyanıqlıqla əlaqələnib. Sayt üçün buradan çıxarılan dizayn qərarı böyük oxu səthlərində rəngi yumşaq, əsas əməliyyatlarda isə daha aydın saxlamaqdır. [Təcrübə](https://doi.org/10.1007/s00426-017-0880-8)
- 30 ölkədə 4 598 iştirakçı ilə aparılan tədqiqat ortaq rəng assosiasiyaları və dilə, coğrafiyaya görə fərqlər tapıb. 128 illik ədəbiyyatın sistematik icmalı assosiasiyanın konkret mühitdə hiss edilən emosiyaya birbaşa çevrilməsinin açıq sual olduğunu göstərir. İsti palitranın Lavbe oxucularında rahatlıq hissini artırması bu mənbələrlə ölçülməyib. [Ölkələrarası tədqiqat](https://www.psychologicalscience.org/journals/psychological-science/0956797620948810/) · [Sistematik icmal](https://doi.org/10.3758/s13423-024-02615-z)
- Mətn kontrastı ayrıca ölçülür. Mətn üzərində xəta tapma təcrübələrində tünd mətnin açıq fonda oxunması daha yaxşı nəticə verib. Gündüz rejimi bu düzülüşdən, gecə isə tünd fonda yüksək kontrastlı açıq mətndən istifadə edir. [Buchner və Baumgartner](https://pubmed.ncbi.nlm.nih.gov/17510822/)

Üç əsas səth üzrə ən aşağı nisbətlər gündüz və gecə üçün müvafiq olaraq belədir: əsas mətn 12.31:1 / 11.11:1, köməkçi mətn 6.07:1 / 6.68:1, sahə sərhədi 3.55:1 / 3.47:1. Əsas düymənin yazısı 7.56:1 / 8.02:1-dir. [WCAG 2.2 kontrast meyarları](https://www.w3.org/TR/WCAG22/#contrast-minimum)

Sistem rejimi ilkin seçimdir. Gündüz və Gecə brauzerdə yadda qalır. İlk görüntüdə uyğun rəng tətbiq edilir. Mətn kontrastı WCAG AA üzrə yoxlanır. Fokus göstəricisi və statusun yazısı rəngdən ayrıca görünür. Sistem şriftləri yerli yazı sisteminə uyğun seçilir. Mətn sahəsi 17–18 px, əsas toxunma sahələri ən az 44 × 44 px, oxu sütunu masaüstündə maksimum 42 rem. Kiçik ekranın minimum sınaq eni 320 CSS px-dir.

## Komponentlər

Moderator iş sahəsi ictimai yazı formasının dar sütunundan ayrılır. Desktop-da solda sabit bölmə naviqasiyası, sağda geniş növbə və iki sütunlu məzmun/qərar sahəsi var; telefonda cari bölməni göstərən native açılan menyu və alt-alta formalar işləyir. Səlahiyyətlər axtarış və mövcud grant siyahısına ayrılır. Eyni Gavalı mürəkkəbi rəng tokenləri, serif başlıq, sans-serif idarə, fokus konturu və 44 px toxunma sahəsi saxlanır. Şəxsi moderator səthində oxucu razılığı banner-i və oxucu footer-i göstərilmir.

Etiraf yazma bölməsi oxucu formasının eyni Blade hissəsini iş sahəsində göstərir. Form panelin eninə uyğunlaşır və mətn sahəsi başlanğıcda görünür; göndəriş yalnız baxış növbəsinə düşür. Moderatora ayrıca mətn limitləri və ya yayım qaydası tətbiq olunmur.

- Başlıq, mobil Oxu/Yaz/Mənim naviqasiyası, masaüstü naviqasiya, düymə, sahə, dialoq, mövzu seçimi, status, bildiriş, kart, skelet, səhifələmə və oflayn ekran.
- Kart hiyerarxiyası: mövzu, unikallaşdırılmış başlıq, təsdiqlənmiş mətndən qısa parça, lazım olduqda Davamı gəldi. Skeleton eyni kart komponentinin geometriyasından istifadə edir.
- 12 kateqoriyanın hər birinin mövzuya uyğun, unikal Lucide SVG-si var. İkon adın yanında həm kateqoriya naviqasiyasında, həm lent kartlarında göstərilir; ikon dekorativdir və mətn etiketi linkin əlçatan adını təmin edir. Yazı formasındakı açıla bilən mövzu bələdçisi adla yanaşı sərhəd izahını göstərir. Kateqoriyanın sabit açarları və ikon xəritəsi [lokalizasiya xəritəsində](../localization.md#kateqoriyaların-sabit-xəritəsi) saxlanır.
- Ana lent lələk favicon-unu saxlayır. Kateqoriya lenti və etiraf səhifəsi həmin kateqoriyanın yerli Lucide SVG-sini və eyni SVG-dən hazırlanmış 32 × 32 px PNG variantını təqdim edir. Kateqoriyasız səhifələr lələk ikonundan istifadə edir.
- Kateqoriya adları lentdə, seçicidə, kateqoriya başlığında və yazı bələdçisində normal şrift qalınlığındadır. Mövzu seçicisində “Hamısı” qalın yazılır. Cari seçim fon və rənglə göstərilir.
- Lucide ikonları yerli SVG dəsti kimi verilir. Yalnız istifadə olunan ikonları saxla, dəstin lisenziya bildirişlərini əlavə et. Etiketli ikonlar dekorativ sayılır, tək ikonlu düymənin əlçatan adı var. Bayraqlar eyni ölçüdə yerli yumru SVG-lərdir, dil etiketi yanında qalır.
- Aşağıdan açılan panellər fokusun geri qaytarılmasını, Escape ilə bağlanmanı və klaviatura idarəsini dəstəkləyir. Təbii düymə və link semantikası qorunur. Azaldılmış hərəkətdə skelet və keçidlər sabitdir.
- Görünüş və hərəkət istifadəçini həssas detal yazmağa sövq etmir. Reklam sahəsi heç bir sistem düyməsinə bənzəmir.

Performans büdcəsi ilkin mobil oxu səhifəsi üçün sıxılmış birinci tərəf CSS ≤35 KB, JS ≤25 KB. Reklam və razılıq skriptləri ayrıca ölçülür.

İstinadlar: [Lucide lisenziyası](https://lucide.dev/license) · [WCAG 2.2](https://www.w3.org/TR/WCAG22/) · [rəng və mədəniyyət araşdırması](https://doi.org/10.1177/0956797620948810)

Oxu lentində axtarış başlığın yanında, mövzular və sıralama isə kartlardan əvvəl yerləşir. Boş lentdən yazı formasına birbaşa keçid var. Dil paneli açılarkən fokus axtarışa keçir. Escape paneli bağlayır və fokusu dil seçiminə qaytarır.

Ortaq naviqasiya etiketləri, dil axtarışı, əsas məzmuna keçid və rejim düyməsinin əlçatan adları 17 dildə navigation.php mənbəyindən alınır. Rejim dəyişəndə JavaScript əlçatan adı həmin dildə yeniləyir.

Lokal məzmun baxışı üçün `php artisan db:seed --class=AzerbaijaniDemoConfessionSeeder` əmri sabit 12 kateqoriyanın hərəsinə ən azı iki Azərbaycan dilində uydurma etiraf əlavə edir. Köhnə yas hekayələri yeni `life-changes` mövzusunda qalır; həmin kateqoriyada dörd uyğun nümunə olur. Valideynlik üçün iki yeni hekayə əlavə edilir. Əmr yalnız local mühitdə işləyir, təkrar icra dublikat yaratmır. Hər nümunənin açıq pointer-i öz approved revision-ına bağlanır. Seeder ayrıca çağırılır və `DatabaseSeeder`-ə qoşulmur.

Lent kartında unikal oxunuş göstəricisi müəllif adının qarşısında, alt footer-in sağ kənarında yerləşir; dar ekranda footer sətirə keçir. Etiraf detail-ində müəllif, tarix və unikal oxunuş sayı köməkçi metadata sətrindədir. Oxunuş göstəricisi ayrıca göz ikonu daşıyır. Şikayət keçidləri mətn kartının sonunda, dəstək və izləmə əməliyyatları aşağıdakı ortaq sırada yerləşir. Rəy və davam formaları ortaq sahə, checkbox və düymə üslubundan istifadə edir.

Ortaq palitra isti krem səthi ilə gavalı vurğusunu, gecə rejimində şabalıdı fon və şaftalı vurğusunu birləşdirir. İnterfeys idarələri sans-serif, lent və detail hekayə başlıqları editorial serif tipoqrafiyasındadır. Lent kartında kateqoriya və tarix yuxarıda, müəllif və unikal oxunuş isə alt footer-in sol və sağında görünür.

Dil seçiminin hər sətrində flag-icons dəstindən yerli SVG bayraq, native ad və onun altında ingiliscə ad göstərilir. Seçilmiş dil işarələnir. Dəstin mənbə commit-i və MIT lisenziyası public/icons/flags qovluğunda saxlanır.

Hesab formalarında hər etiket konkret input ID-sinə bağlıdır. Validasiya xətası sahənin altında göstərilir və aria-describedby ilə həmin sahəyə bağlanır. Checkbox elementləri adi mətn inputunun tam en və minimum hündürlük qaydasından ayrıca ölçülənir.

Eyni etiket və xəta əlaqələri yazı, şikayət, etiraz, bərpa və moderasiya formalarında da tətbiq olunur. Moderator icazələrinin ləğvində hər səbəb sahəsinin ID-si grant ID-sinə görə unikaldır. HTML sınaqları ictimai formalarda etiket hədəflərini, təkrarlanan ID-ləri və aria-describedby istinadlarını yoxlayır.

404, dialoq, yüklənmə və footer mətnləri də cari locale ilə render olunur. Bu UI mətnləri 17 dilin cari runtime paketlərindən götürülüb. Məhsul sahibinin yekun dil baxışı 22 noyabr 2026-da tamamlanıb.

Saytın footer-i solda ürək vurğulu Lavbe wordmark-ını, sağda isə altı hüquqi/məlumat keçidinin kompakt sırasını göstərir. Operator məlumatı səhifəsi ayrıca URL-dən əlçatandır, footer-də göstərilmir. Əlçatan adlı naviqasiya, altxətli keçidlər, 44 px hündürlük, görünən fokus və cari səhifə üçün `aria-current="page"` saxlanır. Link mətnləri yığcam 0.75rem ölçüdədir. Mobil ölçüdə linklər wordmark-ın altına düşüb səliqəli sətirlənir; sabit mobil naviqasiya üçün alt boşluq qalır. Footer-də ayrıca slogan/signature sətri yoxdur.

“Gavalı mürəkkəbi” saytın daimi və yeganə görünüşüdür. Mövcud səhifə skeletini, komponentləri və əməliyyatları saxlayır; səth, ölçü və tipoqrafiya ritmini müəyyən edir. Başlıqlar məhdud editorial ölçü pilləsindən istifadə edir, hekayə mətni rahat oxu enində qalır, desktop mövzu siyahısı yüngül indeks kimi görünür, sıralama tabları bərabər ölçüdə saxlanır. Etiraf, tövsiyə, rəy və forma səthlərində hover/fokus əks-əlaqəsi eyni gavalı vurğusundan istifadə edir. Kiçik ekran, klaviatura fokusu, gündüz/gecə rəng tokenləri və `prefers-reduced-motion` davranışı qorunur. Köhnə görünüş keçidi və cihaz preferensi yoxdur.

Mobil ana lentdə kateqoriyalar “Mövzular” açılan seçiminin arxasında qalır; açıldıqda sıralama tam enə keçir. Desktop-da kateqoriyalar yığcam sol sütunda görünür. Lent kartı hover-capable pointer üzərində sakit kölgə alır, klaviatura ilə linkə fokuslananda isə kart sərhədi fokus halqası ilə vurğulanır; reduced-motion seçimi qorunur.

Feed sıralaması üç bərabər sütunlu tabdır: Yeni üçün sparkles, Bu həftə üçün calendar, Məşhur etiraflar üçün flame ikonu. İkonlar dekorativdir; tabın tərcümə olunmuş mətn etiketi və `aria-current` semantikası əsasdır.


## 2026-11 · Məzmunu önə çəkən UI qərarları

Dolu lentdə hero dəvəti təkrarlanmır; ilk etirafın başlığı və mətni əsas vizual çəkini alır. Mobildə kateqoriyalar mövzu açılan seçiminin arxasında qalır və sıralama ilə yanaşı düzülür. Masaüstündə mövzular yığcam sol sütunda, lent oxu enində mərkəzdədir. Kart parçası üç sətirdən sonra kəsilir. Yazı formasında ilk fokus sırası hekayə mətni, məcburi başlıq, mövzu, sonra bağlı paylaşım seçimləridir; anonimlik açıq deyilir, ad sahəsi bağlı və autocomplete-sizdir. Detail səhifəsində breadcrumb kateqoriyanı bir dəfə göstərir, oxunuş sayğacı hekayədən sonra qalır, dərc olunmuş müəllif davamı əməliyyatlar və rəylərdən qabaq görünür. Bütün dəyişikliklər server HTML və mövcud komponent dəyişənləri ilə işləyir.
