# SEO, AdSense və analitika

İcra səviyyəsində [lent müqaviləsi](architecture/feed-contract.md) və [səth, reklam və analitika matrisi](architecture/traffic-and-privacy.md) tətbiq olunur.

## İctimai URL və indeks müqaviləsi

| Resurs | URL nümunəsi | Qayda |
|---|---|---|
| Dil lenti | `/ru/` | Dilə məxsus HTML, daxili keçid və öz canonical ünvanı |
| Kateqoriya | `/ru/topics/semya` | Eyni dil və sabit kateqoriyaya bağlı yerli slug, kifayət qədər məzmun varsa indeksə açıq |
| Etiraf | `/ru/confession/7k4m2` | Orijinal dil, sabit opaque ID, 200, öz canonical ünvanı, tam HTML |
| Növbəti lent səhifəsi | `/ru/?page=2` | Standart Yeni axınında ayrıca botların izləyə bildiyi HTML və öz canonical ünvanı |
| Axtarış və sıralama kombinasiyası | `/ru/?sort=weekly` | Sitemap xaricində, indeksdən kənar saxlanır. Əsas kateqoriya və etiraf linkləri standart lentdən əlçatandır. |

URL şablonları sabitdir. Yazılan başlıq URL-ə salınmır. Dil seqmenti orijinal məzmunu saxlayır. Səhv dil və ya dəyişmiş slug variantı təhlükəsizdirsə əsas ünvana yönləndirilir. Query parametrlərində şəxsi mətn daşınmır. İctimai lokal link birbaşa açılır və cihaz dili üzrə yönləndirilmir. `hreflang` yalnız qarşılıqlı uyğun səhifələr üçün tam və qarşılıqlı olur. `x-default` uyğun olduqda sabit ingilis fallback səhifəsinə işarə edir. Müstəqil etiraflar bir-birinin tərcüməsi kimi işarələnmir.

HTML başlığı, H1 və təsvir təsdiqlənmiş versiyanı əks etdirir. Metadata və paylaşım önizləməsi moderasiya qərarına daxildir. Google fraqment və başlığı seçə bilər. Kateqoriya sitemap-ə ilkin olaraq həmin dildə beş təsdiqlənmiş etirafla namizəd olur, hədd konfiqurasiya olunur və boş səhifə indeksə açılmır. Fərdi etirafın indeks uyğunluğu kateqoriyanın həddindən asılı deyil. Spam və təsdiqlənməmiş versiya açıq görünmür.

Sitemap indeksini domen kökündə təqdim et. Dil üzrə sabit hissələr yalnız 200 qaytaran canonical URL-ləri saxlayır. Əhəmiyyətli təsdiqlənmiş dəyişiklik `lastmod` dəyərini yeniləyir. Reaksiya, təsadüfi tarix və baxış sayı onu dəyişmir. Geri çəkilmiş URL dərhal siyahıdan çıxır. `robots.txt` indeksə işarə edir və silinmiş ictimai URL-nin statusunu crawler-dən gizlətmir. Google Search Console domen yoxlaması və sitemap təqdimatı bir dəfə edilir. Bing Webmaster Tools və IndexNow yalnız əlavə/dəyişən/silinən URL-lər üçün outbox ilə işləyir. Etiraf üçün Google Indexing API istifadə olunmur. Faktiki indeks və sıralama xarici nəticədir.

## AdSense

Yeganə monetizasiya AdSense-dir. Yayım statusu və reklam uyğunluğu ayrı saxlanır. Uyğunluq seçilən dil, etiraf, davam və bütün açıq rəylər üzrə hesablanır. Məzmun versiyası dəyişəndə qiymətləndirmə və keş yenilənir. Naməlum uyğunluq, dil siyasətində dəstəksiz dil, cari razılıq konfiqurasiyası çatışmazlığı və ya qapalı qlobal açar reklamsız görünüş verir. Azərbaycanca əsas məzmun reklamsızdır. Rus dilinə reklam uyğunluğu və Rusiya regionunda xidmət məhdudiyyəti ayrı konfiqurasiya olunur.

İlkin variant uyğun oxu detalında tam mətnin sonundan sonra bir idarə olunan responsive blokdur. Yazma, ayrıca rəy forması, hesab, kömək/hüquqi, axtarış, boş və aşağı dəyərli səth, lent və oflayn görünüş reklamsızdır. Hərəkət düymələrindən vizual məsafə, rezerv sahə, normal mətn axını. Auto Ads, örtən reklam və avtomatik refresh söndürülür. Yaş, həssas məzmun və regional razılıq siyasəti ayrıca yoxlanır. `ads.txt` yalnız operator tərəfindən təsdiqlənmiş identifikatorla çıxır. Qlobal açar və səhifə səviyyəli dayandırma görünən kodu və cache variantını birlikdə dəyişir. AdSense/CMP xarici aktivləşdirmə buraxılışın reklam qapısıdır.

Active View, AdSense Page RPM, min oxu sessiyasına gəlir, uyğun trafik payı, ikinci etirafa keçid, geri qayıtma və etibarsız trafik siqnalları dil və ölkə üzrə izlənir. Yalnız ilkin model ölçüldükdən sonra bir yerləşim sınağı təsdiqlənə bilər.

## GA4 və razılıq

Bir istehsal GA4 mülkiyyəti, ayrılmış sınaq mühiti. Basic Consent Mode. Analitika seçimi verilmədən GA skripti və sorğusu yüklənmir. Reklam razılığı ayrıca idarə olunur. Yalnız uyğun ictimai oxu səthində əvvəlcədən icazəli hadisələr: clean page view, next confession, dil dəyişikliyi. Parametrlər locale, səhifə növü və sanitizasiya olunmuş ümumi yol ilə məhdudlaşır. Xam URL, referrer və title məruz qalması şəbəkə sınağında yoxlanır. Avtomatik page view, sayt axtarışı və riskli Enhanced Measurement funksiyaları söndürülür. Qonaq kodu, etiraf ID-si və mətn parametri ötürülmür. Şəxsi, yazı, şikayət, bərpa, admin və oflayn axınlar analitikadan kənardır. Google Search Console gedən klik və indeks siqnallarını URL prefiksi ilə, GA4 oxu davranışını content_locale ilə göstərir. Canlı açarlar mühit konfiqurasiyasından gəlir.

Rəsmi mənbələr: [Google çoxdilli saytlar](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites) · [sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap) · [IndexNow](https://www.indexnow.org/documentation) · [AdSense yerləşimi](https://support.google.com/adsense/answer/1346295) · [UGC](https://support.google.com/adsense/answer/1355699) · [məzmuna uyğun dillər](https://support.google.com/adsense/answer/9727) · [CMP](https://support.google.com/adsense/answer/13554116) · [GA və PII](https://support.google.com/analytics/answer/6366371) · [Consent Mode](https://developers.google.com/tag-platform/security/concepts/consent-mode)
