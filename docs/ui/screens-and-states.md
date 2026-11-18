# Ekranlar, ortaq lent və yükləmə

## Ortaq lent şablonu

| Sıra | Mobil | Masaüstü |
|---|---|---|
| 1 | Lavbe, cari dilin yumru bayrağı, dil menyusu | Eyni başlıq, mətnli naviqasiya |
| 2 | Hamısı, cari mövzu, bir neçə mövzu, Bütün mövzular | Eyni mövzu komponenti daha geniş düzülüşdə |
| 3 | Yeni · Bu həftə · Davamı gəldi | Eyni seçimlər |
| 4 | Bir sütun etiraf kartları | Mərkəzlənmiş oxu sütununda eyni kartlar |
| 5 | Daha çox və mobil alt naviqasiya | Eyni səhifələmə və ümumi alt hissə |

Ana lent Hamısı seçimi ilə açılır. Kateqoriya səhifəsi həmin lentin kateqoriya filtrli görünüşüdür. Dil seçimi, kateqoriya, sıralama və səhifələmə serverdə etibarlı qiymətlər üzrə tətbiq edilir. İlk sorğu hazır HTML qaytarır. İlk renderdə skeletlə mətnin üstü örtülmür.

İlkin və növbəti paket 12 kartdır. **Daha çox** adi HTML linkidir. JavaScript sabit sessiya cursor-u ilə kartları əlavə edir. Server query, canonical, sıralama, təkrar və vaxtı bitmiş cursor üçün [lent müqaviləsi](../architecture/feed-contract.md) əsas götürülür. Geri düyməsi dil, mövzu, sıralama və mümkün olduqda oxu mövqeyini bərpa edir.

[Wireframe atlası](wireframes.html) mobil və masaüstü ekranların təsdiq edilə bilən düzülüşünü, gözləmə, boş, xəta və oflayn vəziyyətlərini göstərir.

## Vəziyyətlər

| Vəziyyət | Davranış |
|---|---|
| İlk açılış | Tam HTML, real kartlar və daxili linklər |
| Mövzu keçidi | URL və fokus mənalı qalır, yalnız lent üçün skelet mümkündür, köhnə sorğu yeni seçimi əvəz etmir |
| Daha çox | Mövcud kartlar qalır, əlavə yerdə eyni ölçülü skelet görünür, yeni sorğu vaxtı düymə təkrarlanmır |
| Boş | Cari dildə səbəb və uyğun hərəkət. Başqa dildən etiraf köçürülmür. |
| Xəta | Mövcud kartlar qalır. Yenidən cəhd et, lokal mesaj və status. |
| Oflayn | Mövcud açıq səhifə işlək qaldığı qədər görünür. Yeni naviqasiyada lokallaşdırılmış oflayn görünüş verilir. |
| Son səhifə | Növbəti səhifə olmadığı görünür. Düymə və skelet çıxarılır. |

## Digər ekranların quruluşu

- **Etiraf:** mövzu, başlıq, anonim ad, tam mətn, Sonra nə oldu, uyğun reklam və oxu hərəkətləri. Rəylər bir səviyyəlidir. Rəy yaz ayrı reklamsız səth açır. Rəy şikayəti konkret rəyin menyusundadır. Sonra eyni dildən etiraf təklif olunur.
- **Yaz və rəy:** geniş mətn sahəsi, yığcam əlavə seçimlər, yerli xəta və baxışa göndər düyməsi. Mobil klaviatura kursoru və fokuslu sahəni örtmür. Şəbəkə xətasında DOM-dakı mətn qalır. Cavab alınana qədər uğurlu status göstərilmir.
- **Göndəriş və bərpa:** bərpa kodunun ayrıca görünüşü, kopyalama və Mənim bölməsinə keçid. URL-də və açıq keşdə kod yoxdur.
- **Mənim:** Etiraflarım, Rəylərim, İzlədiklərim və Bildirişlər. Rejim, giriş, bərpa və quraşdırma əlçatandır.
- **Dil seçici:** masaüstündə düymənin altında, mobil cihazda aşağı panel. Axtarış, yumru bayraq, native ad, altında ingiliscə ad, seçilmiş dildə işarə. Mənbə [dil reyestri](../localization.md). JavaScript olmadan dil linkləri açılır.
- **Admin:** bir ekran, Etiraflar/Rəylər/Şikayətlər. Təcili işlər önə çıxır. Qalanlar ən köhnə gözləyənlə başlayır. Kartda qərar, səbəb və kontekst. Səlahiyyət olmadıqda məlumat render edilmir.
- **Hüquqi, 404/410, oflayn:** ortaq başlıq və tipoqrafiya, lokallaşdırılmış mesaj, əsas dil lentinə link.
