# Ekranlar, ortaq lent və yükləmə

## Ortaq lent şablonu

| Sıra | Mobil | Masaüstü |
|---|---|---|
| 1 | Lavbe, cari dilin yumru bayrağı, dil menyusu | Eyni başlıq, mətnli naviqasiya |
| 2 | Yığcam “Mövzular” açılan seçimi, içində bütün 12 kateqoriya | Sol sütunda 12 mövzulu kompakt siyahı, oxu lenti mərkəzdə |
| 3 | Yeni, Bu həftə və Məşhur etiraflar — fərqli ikonlu, bərabər ölçülü tablar; seçilmiş sıralama mövzu seçiminin yanındadır | Eyni tablar oxu lentinin üstündə |
| 4 | Bir sütun etiraf kartları | Mərkəzlənmiş oxu sütununda eyni kartlar |
| 5 | Daha çox və mobil alt naviqasiya | Eyni səhifələmə və ümumi alt hissə |

Ana lent Hamısı seçimi ilə açılır. Kateqoriya səhifəsi həmin lentin kateqoriya filtrli görünüşüdür. Dil seçimi, kateqoriya, sıralama və səhifələmə serverdə etibarlı qiymətlər üzrə tətbiq edilir. İlk sorğu hazır HTML qaytarır. İlk renderdə skeletlə mətnin üstü örtülmür.

Kateqoriya səhifəsinin başlığı altında seçilmiş mövzunun həmin dildə izahı görünür. Etiraf yazı formasındakı mövzu bələdçisi açıla bilən siyahıdır; JavaScript olmadan da 12 mövzunun adını və sərhədini oxumaq mümkündür.

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

- **Ana lent:** Dolu lentdə giriş mətni gizlənir və ilk etiraf erkən başlayır. Mobildə “Mövzular” seçimi bütün kateqoriyaları açır; açılanda sıralama tam enə keçir. Desktop-da mövzular yığcam sol naviqasiyadır, lent mərkəzdə qalır. Etiraf kartları üç sətirlik parça, hover və klaviatura fokusunda yüngül əks əlaqə verir.
- **Etiraf:** mövzu, başlıq, anonim ad, tam mətn, Sonra nə oldu, uyğun reklam və oxu hərəkətləri. Rəylər bir səviyyəlidir. Rəy yaz ayrı reklamsız səth açır. İcma şikayəti və hüquqi bildiriş konkret etiraf və ya rəy hədəfinə bağlanır. Sahibin gizlətməyə etirazı yalnız özəl status səhifəsində görünür. Sonra eyni dildən etiraf təklif olunur.
- **Yaz və rəy:** əvvəlcə geniş mətn sahəsi, sonra məcburi başlıq və mövzu, bağlı əlavə seçimlər, yerli xəta və baxışa göndər düyməsi. Anonimlik görünən şəkildə bildirilir; açıq ad seçimi ilk renderdə bağlıdır. Mobil klaviatura kursoru və fokuslu sahəni örtmür. Şəbəkə xətasında DOM-dakı mətn qalır. Cavab alınana qədər uğurlu status göstərilmir.
- **Göndəriş və bərpa:** bərpa kodunun ayrıca görünüşü, kopyalama və Mənim bölməsinə keçid. URL-də və açıq keşdə kod yoxdur.
- **Mənim:** Etiraflarım, Rəylərim, İzlədiklərim və Bildirişlər. Rejim, giriş, bərpa və quraşdırma əlçatandır.
- **Dil seçici:** header-də seçilmiş dilin bayrağı və native adı tam yazılır; qısa locale kodu göstərilmir. Mobil enlərdə yalnız bayraq görünür, native ad screen reader üçün əlçatan qalır. Açılan menyuda axtarış, yumru bayraq, native adın altında ingiliscə ad və seçilmiş dil işarəsi var. Mənbə [dil reyestri](../localization.md). JavaScript olmadan dil linkləri açılır.
- **Admin:** Etiraflar, Rəylər, Şikayətlər və Etirazlar üçün ayrı növbələr. Hüquqi və təcili qruplar önə çıxır; qalanlar ən köhnə gözləyənlə başlayır. Kartda qərar, səbəb və kontekst. Səlahiyyət olmadıqda məlumat render edilmir.
- **Hüquqi, 404/410, oflayn:** ortaq başlıq və tipoqrafiya, lokallaşdırılmış mesaj, əsas dil lentinə link.


Etiraf detail-də breadcrumb kateqoriyanı göstərdiyi üçün başlıq sahəsində kateqoriya təkrarlanmır. Müəllif və oxunaqlı yerli tarix yığcam meta sətrindədir; oxunuş göstəricisi hekayədən sonra qalır. Təsdiqlənmiş müəllif davamı mövcud olduqda, dəstək və şərhlərdən əvvəl verilir.

## Tapşırıq əsasında UX yoxlaması

Etiraf platformasında uğur meyarı ekranda qalma müddəti deyil. Oxucu hekayəni tapa bilir, müəllif nəyi kimə göndərdiyini anlayır və paylaşımına təhlükəsiz qayıda bilir. Sınaqda aşağıdakı tapşırıqları iştirakçıya düymələrin adını demədən verin.

| Tapşırıq | Sınaqda yaradılası çətinlik | Müşahidə edilən nəticə |
| --- | --- | --- |
| Bir hekayəni aç və ona münasibət bildir | Hekayəyə axtarış nəticəsindən daxil ol | Oxucu düzgün hekayəyə rəy yazır və rəyin dərhal açıq olub-olmadığını izah edir |
| Etiraf göndər və başlığı düzəlt | Server dolu mətn və boş başlıq alır | Fokus xülasəyə gəlir, keçid başlığa aparır, mətn təkrar yazılmır |
| Cihazdan etirafına qayıt | Eyni cihazda saxlanmış və yeni brauzer sessiyalarını ayrıca sına | Müəllif öz paylaşımını tapır və ortaq cihazda girişi ləğv etməyin nəticəsini anlayır |
| Dilini dəyiş | Əvvəl uyğunluğu olmayan söz, sonra dilin yerli adı daxil edilir | Boş nəticə elan edilir, Enter səhv dili seçmir, Escape menyunu bağlayır |
| Narahatedici rəyi bildir | Etiraf və rəy eyni ekrandadır | İştirakçı düzgün hədəfi seçir və şikayətin kimə getdiyini izah edir |

Forma xətasını sahənin yanında və fokuslana bilən xülasədə göstəririk. Uzun mobil formada istifadəçi səhvi tapmaq üçün bütün sahələri yenidən oxumamalıdır. Fokusun sabit naviqasiya altında gizlənməsini klaviatura, böyüdülmüş mətn və açıq mobil klaviatura ilə ayrıca yoxlayın. [W3C forma bildirişləri](https://www.w3.org/WAI/tutorials/forms/notifications/) · [WCAG 2.2 fokusun görünməsi](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html)

**Yerli texniki sınaq.** Sintetik hekayələrlə Chromium-da 390 px enində oxuma, server xətasından qayıtma, uğurlu göndərişdən sonra cihazdan tapma və dil seçimi yoxlanılıb. Ana səhifə və yazı forması 320, 390 və 1440 px enində üfüqi daşma yaratmayıb.

**İnsanlarla yoxlama.** İlk dəfə gələn oxucular, müəlliflər və klaviatura və ya ekran oxuyucusu istifadə edən iştirakçılarla sintetik hekayələr üzərində çalışın. Şəxsi etiraf istəməyin. Hər tapşırıq üçün müstəqil tamamladı, köməklə tamamladı və ya yarımçıq qoydu nəticəsini, səhv dönmələri və iştirakçının gözləntisini qeyd edin. Ortaq cihazda başqasına giriş vermək və yanlış hədəfi şikayət etmək kritik səhvlərdir. Hər belə müşahidədən sonra axını düzəldib təkrar sınayın. Dil, cihaz və köməkçi texnologiyanı nəticə ilə birgə qeyd edin. [Nielsen Norman Group istifadə rahatlığı sınaqları](https://www.nngroup.com/articles/usability-testing-101/)
