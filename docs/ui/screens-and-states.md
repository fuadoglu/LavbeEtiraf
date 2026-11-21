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

600 px və aşağıda əsas naviqasiya ekranın altındadır. 600 px-dən geniş və 840 px-dək ekranlarda Oxu/Yaz/Mənim etiketləri yuxarıda qalır, dil seçimində bayraq göstərilir. 840 px-dən geniş ekranda bayraqla yanaşı dilin tam adı görünür. Masaüstü mövzu sütunu 761 px-dən başlayır.

Kateqoriya səhifəsinin başlığı altında seçilmiş mövzunun həmin dildə izahı görünür. Etiraf yazı formasındakı mövzu bələdçisi açıla bilən siyahıdır; JavaScript olmadan da 12 mövzunun adını və sərhədini oxumaq mümkündür.

Hər səhifədə ən çox 12 kart olur. Nömrəli pagination adi HTML linklərindən ibarətdir və JavaScript olmadan işləyir. Geri düyməsi dil, mövzu və sıralamanı saxlayır; oxu mövqeyi brauzerin standart scroll bərpası ilə idarə olunur. [Lent müqaviləsi](../architecture/feed-contract.md) canonical URL, sort və public visibility qaydalarını müəyyən edir.

Yeniləmə cari lent URL-sindəki kateqoriya, sıralama və səhifə nömrəsini saxlayır. Dil seçimi 303 ilə GET səhifəsinə keçir. Yeni dildə lent eyni mövzu və sıralama ilə birinci səhifədən başlayır. Yazı, hüquqi səhifə, axtarış və əsas hesab ekranlarında həmin ekranın seçilmiş dil variantı açılır. Etirafın orijinal dil URL-si sabit qalır və dil seçimi həmin dildəki ana lentə aparır. Axtarış sorğusu POST gövdəsində qalır, dillər arasında ötürülmür.

Başlıq və mətn yazıldıqca bu brauzerdə saxlanır. Yeniləmə, dil keçidi və formaya sonradan qayıtma zamanı qaralama bərpa olunur. Hər iki sahə boşaldılanda və ya qaralamaya uyğun göndəriş uğurla tamamlananda saxlanmış dəyərlər silinir.

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
- **Etiraf:** mövzu, başlıq, müəllif adı, tam mətn, Sonra nə oldu və oxu hərəkətləri. Rəylərə bir səviyyədə cavab vermək və səhifənin altında rəy yazmaq mümkündür. İcma şikayəti və hüquqi bildiriş konkret etiraf və ya rəy hədəfinə bağlanır. Sahibin gizlətməyə etirazı yalnız özəl status səhifəsində görünür. Sonra eyni dildən etiraf təklif olunur.
- **Yaz və rəy:** etiraf formasında geniş mətn sahəsindən sonra məcburi başlıq və mövzu seçimi gəlir. Paylaşım seçimləri və istəyə bağlı açıq ad sahəsi birbaşa görünür. Forma xətası müvafiq sahənin yanında göstərilir. Cavab alınana qədər uğurlu status göstərilmir.
- **Göndəriş və bərpa:** bərpa kodunun ayrıca görünüşü, kopyalama və Mənim bölməsinə keçid. URL-də və açıq keşdə kod yoxdur.
- **Mənim:** Etiraflarım, Rəylərim, İzlədiklərim və Bildirişlər. Rejim, giriş, bərpa və quraşdırma əlçatandır.
- **Bu cihazdakı etiraflar:** Qonaq boş siyahıdan birbaşa yeni etiraf yaza bilər. Saxlanmış etirafın başlığı onun vəziyyət səhifəsinə aparır və cari status kartda göstərilir. Hesab keçidləri siyahıdan ayrıdır. “Bu cihazı unut” əməliyyatından əvvəl onun izahı açıla bilir.
- **Dil seçici:** header-də seçilmiş dilin bayrağı və native adı tam yazılır; qısa locale kodu göstərilmir. Mobil enlərdə yalnız bayraq görünür, native ad screen reader üçün əlçatan qalır. Açılan menyuda axtarış, yumru bayraq, native adın altında ingiliscə ad və seçilmiş dil işarəsi var. Mənbə [dil reyestri](../localization.md). JavaScript olmadan dil linkləri açılır.
- **Admin:** Etiraflar, Rəylər, Şikayətlər və Etirazlar üçün ayrı növbələr. Hüquqi və təcili qruplar önə çıxır; qalanlar ən köhnə gözləyənlə başlayır. Kartda qərar, səbəb və kontekst. Səlahiyyət olmadıqda məlumat render edilmir.
- **Hüquqi, 404/410, oflayn:** ortaq başlıq və tipoqrafiya, lokallaşdırılmış mesaj, əsas dil lentinə link.


Etiraf detail-də breadcrumb kateqoriyanı göstərdiyi üçün başlıq sahəsində kateqoriya təkrarlanmır. Müəllif və oxunaqlı yerli tarix yığcam meta sətrindədir; oxunuş göstəricisi hekayədən sonra qalır. Təsdiqlənmiş müəllif davamı mövcud olduqda, dəstək və şərhlərdən əvvəl verilir.

## Gavalı mürəkkəbi

Editorial dizayn bütün səhifələrdə standart və yeganə görünüşdür. Əvvəlki klassik stil seçimi və cihazda yadda saxlanması yoxdur. Ümumi skelet, naviqasiya, məzmun və əməliyyatlar dəyişmir; tipografiya, ölçü, boşluq və səthlər vahid editorial dilə uyğundur. Gündüz/gecə görünüşü ilə birlikdə işləyir.

Seçilmiş sıralama yumşaq fon və sərhədlə seçilir. Lent kartında kateqoriya və tarix, alt sətirdə isə müəllif və oxunuş sayı eyni ölçü pilləsini bölüşür. Etiraf səhifəsində oxunuş sayı mətnin altında oxu istiqamətinin son kənarına düzülür, əməliyyatlar bərabər ölçülü qalır. Yazı formasında simvol sayğacı sabit enli rəqəmlərdən istifadə edir və açıq paylaşım seçimləri ayrıca səthdə qruplaşır.

Lent və axtarış kartları səhifə fonundan azca seçilən səth, incə sərhəd və yumşaq künclərlə ayrılır. Mausla üzərinə gəldikdə sərhəd, klaviatura ilə keçdikdə isə fokus halqası görünür.

### İkinci dərəcəli ekranlar

Etiraf formasında mətn sahələri və seçimlər eyni səth, künc və sərhəddən istifadə edir. Açılan seçim siyahısı brauzerin və cihazın öz idarəsi ilə işləyir. Checkbox sətirləri bütövlükdə toxunula bilir və seçili vəziyyəti göstərir.

Yaş təsdiqində paylaşım qaydaları formanın cari dilindəki icma qaydalarına keçid verir.

Axtarış sahəsi dar ekranda düymənin üstündə, geniş ekranda onun yanında yerləşir. Hesab, cihaz və moderasiya qeydləri eyni kart ölçüləri və başlıq üslubundan istifadə edir. Moderasiya filtrləri dar ekranda yeni sətrə keçir və cari filtr seçilir. Forma xətası sahə izahında, status isə ikon və mətnlə görünür. Dialoq başlıqları, hüquqi mətn, 404 və oflayn görünüş eyni başlıq ritmini izləyir. Səhifələmə idarələrinin hündürlüyü ən az 44 CSS piksel qalır.

Jurnal üslubunda axtarış, şikayət, hesab, moderasiya və 404 başlıqları eyni tipoqrafik qaydaya tabedir. Hüquqi səhifələrdə abzaslar aralıdır. Şikayət və etiraz qərarlarında uzun düymələr dar ekranda yeni sətrə keçir. Köməkçi mətni olan səhv sahənin sərhədi xəbərdarlıq rəngini saxlayır. İlk mobil ziyarətdə oxunuş ölçümünün hər iki seçimi eyni enli sahələrə yerləşir.
