# Dizayn sistemi

Vahid Blade komponentləri ictimai sayt, hesab, admin, hüquqi və xəta səhifələrində tətbiq olunur. Mərkəzi CSS dəyişənləri rəng, boşluq, tipoqrafiya, toxunma sahəsi, fokus və keçid vaxtlarını idarə edir. Dil mətnləri komponentə parametr kimi gəlir.

## Rəng və rejim

| Rol | Gündüz | Gecə |
|---|---|---|
| Fon | `#FAF7F2` | `#181521` |
| Səth | `#FFFDF9` | `#231E30` |
| Əsas mətn | `#292430` | `#F2EDF7` |
| Köməkçi mətn | `#5D5762` | `#C3B8CD` |
| Əsas düymə və yazısı | `#64469A` / `#FFFFFF` | `#C9B4FC` / `#221638` |
| Aktiv sahənin sərhədi | `#8B8092` | `#8F819F` |

Sistem rejimi ilkin seçimdir. Gündüz və Gecə brauzerdə yadda qalır. İlk görüntüdə uyğun rəng tətbiq edilir. Mətn kontrastı WCAG AA üzrə yoxlanır. Fokus göstəricisi və statusun yazısı rəngdən ayrıca görünür. Sistem şriftləri yerli yazı sisteminə uyğun seçilir. Mətn sahəsi 17–18 px, əsas toxunma sahələri ən az 44 × 44 px, oxu sütunu masaüstündə maksimum 42 rem. Kiçik ekranın minimum sınaq eni 320 CSS px-dir.

## Komponentlər

- Başlıq, mobil Oxu/Yaz/Mənim naviqasiyası, masaüstü naviqasiya, düymə, sahə, dialoq, mövzu seçimi, status, bildiriş, kart, skelet, səhifələmə və oflayn ekran.
- Kart hiyerarxiyası: mövzu, unikallaşdırılmış başlıq, təsdiqlənmiş mətndən qısa parça, lazım olduqda Davamı gəldi. Skeleton eyni kart komponentinin geometriyasından istifadə edir.
- Lucide ikonları yerli SVG dəsti kimi verilir. Yalnız istifadə olunan ikonları saxla, dəstin lisenziya bildirişlərini əlavə et. Etiketli ikonlar dekorativ sayılır, tək ikonlu düymənin əlçatan adı var. Bayraqlar eyni ölçüdə yerli yumru SVG-lərdir, dil etiketi yanında qalır.
- Aşağıdan açılan panellər fokusun geri qaytarılmasını, Escape ilə bağlanmanı və klaviatura idarəsini dəstəkləyir. Təbii düymə və link semantikası qorunur. Azaldılmış hərəkətdə skelet və keçidlər sabitdir.
- Görünüş və hərəkət istifadəçini həssas detal yazmağa sövq etmir. Reklam sahəsi heç bir sistem düyməsinə bənzəmir.

Performans büdcəsi ilkin mobil oxu səhifəsi üçün sıxılmış birinci tərəf CSS ≤35 KB, JS ≤25 KB. Reklam və razılıq skriptləri ayrıca ölçülür.

İstinadlar: [Lucide lisenziyası](https://lucide.dev/license) · [WCAG 2.2](https://www.w3.org/TR/WCAG22/) · [rəng və mədəniyyət araşdırması](https://doi.org/10.1177/0956797620948810)

Oxu lentində axtarış başlığın yanında, mövzular və sıralama isə kartlardan əvvəl yerləşir. Boş lentdən yazı formasına birbaşa keçid var. Dil paneli açılarkən fokus axtarışa keçir. Escape paneli bağlayır və fokusu dil seçiminə qaytarır.

Ortaq naviqasiya etiketləri, dil axtarışı, əsas məzmuna keçid və rejim düyməsinin əlçatan adları 17 dildə navigation.php mənbəyindən alınır. Rejim dəyişəndə JavaScript əlçatan adı həmin dildə yeniləyir.
