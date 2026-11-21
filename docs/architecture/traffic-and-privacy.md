# Səthlər, razılıq, reklam və keş müqaviləsi

[SEO və monetizasiya qərarları](../seo-ads-analytics.md) ayrıca izah olunur. Bu cədvəl HTTP səviyyəsində tətbiqin istinadıdır. Analytics və reklam razılığı müstəqil siqnallardır. İstehsal açarları və CMP təsdiqlənənədək inteqrasiyalar söndürülür.

| Səth | AdSense | GA4 | HTML / keş |
|---|---|---|---|
| Açıq dil lenti, mövzu | Xeyr | Razılıqdan sonra ümumi oxu hadisələri | İctimai görünüş, şəxsiləşdirilməmiş |
| Açıq etiraf detalı | Bölmənin bütün şərtləri keçəndə bir blok | Razılıqdan sonra təmizlənmiş və ümumiləşdirilmiş hadisə | Yalnız cari approved public revision. `public, no-cache, must-revalidate`; hər revalidation canlı görünürlük statusunu yoxlayır. Şəxsi göstərici və razılıq vəziyyəti yoxdur. |
| Rəy və müəllif cavabı yazma | Xeyr | Xeyr | Ayrı, reklamsız `/{locale}/participate/...` səthi. `private, no-store`, noindex. |
| Etiraf yaz, redaktə, önizləmə, şikayət | Xeyr | Xeyr | `private, no-store`, noindex. URL və referrer-də mətn və sirr yoxdur. |
| Mənim, hesab, bərpa, bildiriş və admin | Xeyr | Xeyr | İcazə yoxlaması, `private, no-store`, noindex |
| Axtarış, 404/410, oflayn, hüquqi səhifələr | Xeyr | Xeyr | Səthə uyğun keş. Axtarış mətnini və şəxsi məlumatı saxlamır. Sitemap, robots və manifest HTML analitika etiketləri daşımır. |

Etiraf detalındakı **Rəy yaz** ayrı formaya adi keçid edir. JavaScript vizual keçidi yaxşılaşdıra bilər. Reklam skriptinin artıq işə düşdüyü səhifədə məxfi forma açılmır. Forma göndərildikdən sonra təmiz oxu URL-sinə və ya status səthinə keçilir. İstifadəçinin yazdığı mətn query parametrinə çevrilmir. Təkcə CSS ilə reklamı gizlətmək məxfilik meyarını keçmir.

## AdSense qərarı

Uyğunluq ardıcıllığı: qlobal açar açıq → səth etiraf detalıdır → səhifə açıqdır → görünən məzmun dil və region üzrə uyğundur → cari etiraf, davam, rəylər və cavablar baxış qərarına uyğundur → aşağı dəyərli/boş səhifə deyil → CMP və tətbiq olunan razılıq vəziyyəti işləkdir. Dəyərlərdən biri məlum deyilsə reklam bloku və reklam sorğusu çıxmır.

Rəy və davam üçün reklam qərarı məzmunun təsdiqlənən versiyasına bağlanır. Yeni görünən versiya yoxlanılmadan reklamın aktiv qalması mümkün deyil. Keş müştərilər arasında razılıq daşımır. Etiraf detalının həssas hissəsi cari versiya və uyğunluqla serverdə yoxlanır, reklam başlatma yalnız həmin cavabda sahib olduğu siqnal və CMP nəticəsi ilə mümkün olur. Rəy uyğunluğu dəyişəndə tarixi reklamlı cache variantı xidmət edilmir. Qlobal söndürmə yeni HTML və canlı reklam başlatma yolunda yoxlanır. Artıq istifadəçinin cihazına gəlmiş reklamı geriyə çağırmaq vəd edilmir.

İlkin yerləşim etirafın tam mətnindən sonra, davam və oxu hərəkətlərindən vizual ayrılmış responsive sahədir. Domen və ads.txt hazır olmalıdır. Auto Ads, ekranı örtən yerlər, avtomatik refresh və istifadəçiyə klik çağırışı aktiv deyil. AdSense-in faktiki işləməsi region və operator tərəfindən ayrıca doğrulanır.

## GA4 hadisə müqaviləsi

| Hadisə | Göndərilən parametr |
|---|---|
| `page_view` | `content_locale`, `page_type`, `page_location` yalnız təmiz səth ünvanı, `page_title` yalnız ümumi səth adı, `page_referrer` yalnız icazəli ümumi səth və ya boş |
| `next_confession` | `content_locale`, `page_type` |
| `locale_change` | `content_locale`, `target_locale`, `page_type` |

Əlavə `user_id`, etiraf/rəy ID-si, müəllif aliası, kateqoriya marağı, mətn, açar, e-poçt, sətir referrer-i və sərbəst parametr qəbul edilmir. Varsayılan page view və Enhanced Measurement avtomatik göndərişləri söndürülür. Razılıqdan sonra da tag daxilində təmiz location, title və referrer hər başlanğıcda təyin olunur. Hadisə gövdəsini yoxlamaqla kifayətlənmə. DevTools və sınaq izləyicisi bütün şəbəkə yolunu, implicit parametrləri, URL və referrer-i yoxlasın. İcazəsiz və ya qeyri-müəyyən vəziyyətdə GA4 yüklənmir.

Sintetik qəbul nümunələri:

- Razılıqdan əvvəl analitika endpointlərinə **0** sorğu. Razılıqdan imtina, sonra qəbul və sonra geri alma ayrı sınaqlardır. Geri alma yeni göndərişləri dayandırır.
- Açıq rus etirafında qəbul edilmiş analitika üçün nümunə `content_locale=ru`, `page_type=confession`, `page_location=https://lavbe.com/ru/confession`, `page_title=Confession`. Canlı etirafın slug və ID-si, axtarış query-si, mətni və aliası yoxdur. Təmiz ünvan analitika etiketi üçündür, canonical HTML ünvanı dəyişmir.
- Oxu səhifəsində GA4 razılığı var, reklam razılığı seçilməyib. GA4 müvafiq konfiqurasiya ilə işləyə bilər, AdSense CMP-nin icazə verdiyi və təsdiqlənmiş qaydaya tabe qalır.
- Aktiv AdSense-dən rəy formuna keç. Yeni sənəddə AdSense və GA4 skripti/sorğusu yoxdur. Sonra uyğun olmayan rəyin təsdiqini sına və yeni oxu cavabının reklamsız olduğunu yoxla.
- Azərbaycanca etiraf sitemap və oxu axınında var, AdSense kodu yoxdur. Təsdiqlənməmiş və ya geri çəkilmiş mətnin reklam və analitika tələb edən açıq cavabı yoxdur.

İstinadlar: [GA4 və şəxsi məlumat](https://support.google.com/analytics/answer/6366371) · [Basic Consent Mode](https://developers.google.com/tag-platform/security/concepts/consent-mode) · [AdSense UGC](https://support.google.com/adsense/answer/1355699)

[Sınaqlar: ADV-01, ADV-02 və ADV-03](../delivery/acceptance.md)

Sessiya sürücüsü database olduqda validasiya zamanı flash edilən etiraf/rəy mətni sessiya payload-unda şifrəli saxlanır (`SESSION_ENCRYPT=true`). İstehsal APP_KEY və backup ayrılığı, sessiya saxlama müddəti və backup retention operator tərəfindən ayrıca təsdiqlənməlidir; şifrələmə bu əməliyyat qərarlarını əvəz etmir.
