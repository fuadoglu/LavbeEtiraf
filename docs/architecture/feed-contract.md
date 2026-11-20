# Lent, səhifələmə və axtarış müqaviləsi

Bu sənəd ana və kateqoriya lentinin server, JavaScript və botlar üçün vahid davranışını müəyyən edir. Görünüş [ekran atlasındadır](../ui/screens-and-states.md).

## İki giriş, eyni açıq məzmun qaydası

| Giriş | Məqsəd | Müqavilə |
|---|---|---|
| Açıq HTML | İlkin render, JavaScript-siz keçid, botlar | `/{locale}/`, `/{locale}/topics/{slug}`, sonrakı səhifə `?page=2`. Hər səhifə 12 kart və növbəti əvvəlcədən tanınan HTML linki verir. |
| Davam fragmenti | Daha çox düyməsi və mövqe qorunması | `/{locale}/feed-fragment?cursor=…`. Server ilk HTML-də imzalı cursor verir. Hər cavab HTML fragment, növbəti URL və cursor, sona çatma göstəricisi verir. |
| Açıq axtarış forması | Həmin dildə təsdiqlənmiş etiraf tapmaq | `/{locale}/search`. Açılış GET-dir, axtarış mətni POST ilə ötürülür. İctimai cavab noindex, no-store, reklamsız, analitika sorğusu göndərmir. |

## İcra sərhədi (LAV-003)

`/{locale}/` və `/{locale}/topics/{key}` serverdə ilkin HTML və 12-lik paginator verir. Read model `confessions` cədvəlindən yalnız `status=public`, `published_at` dolu və cari locale qeydlərini oxuyur. Sınaq məlumatı yalnız test factory-dədir; production seeder yoxdur. Yeni sıra `published_at DESC, id DESC`-dir. Həftəlik rejim son 7 günü eyni xronoloji sırada göstərir; updates rejimi `updated_at DESC, id DESC` istifadə edir. Bunlar ilkin oxu sıralarıdır, sabit snapshot və hesablama balı hələ deyil.

Cursor Laravel `Crypt` ilə autentifikasiya/şifrələmə olunur; locale, kateqoriya, order, offset, versiya və 60 dəqiqəlik bitmə vaxtına bağlanır. Fragment endpoint HTML qaytarır, `X-Robots-Tag: noindex` və `Cache-Control: no-store` verir. Axtarış GET formu göstərir və POST ilə axtarır; mətn URL-də saxlanmır, nəticələr locale və public status ilə məhdudlaşır, cavab noindex/no-store/private/no-referrer-dir. DB-də tam mətn axtarışı üçün bu scope-da sadə LIKE istifadə olunur.

LAV-003-də snapshot cədvəli, visibility revision, support/comment cədvəllərinə əsaslanan score, Tokyo/Seoul tokenizer və MySQL full-text index yoxdur. Həftəlik/updates snapshot və ranking LAV-004/005 məzmun həyat dövrü ilə birlikdə əlavə edilməlidir. Tətbiq hazırkı modeli onların mövcud olduğunu iddia etmir.

Hər iki lent eyni sorğuda `locale`, kateqoriya və cari açıq vəziyyəti yoxlayır. Açıq başlıq, snippet və kart yalnız təsdiqlənmiş versiyadan gəlir. Sıralama `new`, `weekly` və `updates` allowlist-dən gəlir. Əsas standart `new`-dur, axtarış motoruna təqdim olunan ardıcıl səhifələr də bu sıradadır.

## Dəqiq səhifələmə

- **Yeni:** `published_at DESC, id DESC`. İlk oxu üçün serverin `as_of` sərhədi, son kartın sort cütü, locale/kateqoriya və görünürlük ardıcıllığının yuxarı sərhədi cursor-a bağlıdır. Etiraf hər dəfə açıq lentə daxil olanda yeni monoton görünürlük nömrəsi alır. Sonrakı paket yalnız cütdən sonra gələn, cari açıq kartları oxuyur. Yeni və yenidən açılmış etiraflar ilkin sessiyaya girmir, görünməz edilən etiraflar isə dərhal çıxır.
- **Davamı gəldi** və **Bu həftə:** hər locale və filtr üçün sırası sabitlənmiş, versiyalı snapshot. Snapshot ID və son sıra nömrəsi cursor-a bağlıdır, hər paket göstərməzdən əvvəl həmin ID-lərin cari açıq vəziyyətini yoxlayır. Mövcud olmayan və ya gizli qeydi keç, sona qədər uyğun kartla doldur. Son sıra göstərilən son kartla deyil, baxılmış son snapshot mövqeyi ilə müəyyən edilir. Sıra bir buraxılışda yenilənsə də aktiv oxu sessiyasının snapshot-ı TTL müddətində qalır.
- Həftəlik xal son yeddi gün ərzində unikal dəstək üçün 1, təsdiqlənmiş unikal rəyçi üçün 2-dir. Xal bərabərdirsə yayım tarixi və ID eyni sıra yaradır. Davam sıralamasında son təsdiqlənmiş canlı davam və ID istifadə olunur. Snapshot və cursor ilkin olaraq 60 dəqiqə saxlanır. Səhifə yenilənəndə yeni sıra başlayır. Snapshot ölçüsü real DB yükündə ölçülür, məhdudiyyət olduqda interfeys seçilmiş axın kimi davranır, bütün etiraflar Yeni arxivindən əlçatan qalır.
- JavaScript-siz indekslənən səhifələr cari oxuna bilən arxivin görünüşüdür. Ayrı zamanlarda açılan nömrəli səhifələrdə arxiv dəyişə bilər. İstifadəçi üçün eyni oxu sessiyasındakı dəqiq davam cursor vasitəsilə verilir. JavaScript varsa `Daha çox` həmin cursor-u istifadə edir, adi link qüvvədə qalır. Yeni etirafın yayımını gecikdirmədən həm SEO arxivi, həm də sabit oxu davamı təmin olunur.

## Fragment cavabı və xəta

Cursor məzmun və ya istifadəçi məlumatı daşımır. Locale, order, kateqoriya açarı, snapshot/sərhəd, son mövqe, versiya və bitmə vaxtı imzalanır. Cursor başqa filtrə köçürüləndə, imza pozulanda və ya format səhvdirsə `400`. Bitmiş və ya itmiş snapshot `410` qaytarır, UI köhnə kartları saxlayıb **Lenti yenilə** göstərir. Lenti yenilə əməliyyatı cari səhifəni yenidən açır və URL-dəki dil/filtr/sıralama seçimini saxlayır. Keçici şəbəkə xətasında **Yenidən cəhd et** eyni cursor-la işləyir. Cavab yalnız əsas lentin cari sorğu ID-si ilə uyğun gələndə əlavə olunur. Təkrarlanan kart ID-si müştəridə də çıxarılır.

Fragment cavabı `{ html, next_url, next_cursor, has_more }` formasındadır. Son səhifədə hər iki next dəyəri null olur. Fragment endpoint `X-Robots-Tag: noindex` qaytarır, sitemap və canonical arxivə daxil olmur. Canonical HTML səhifələri öz URL-sinə işarə edir. `page=1` əsas URL-ə, mövcud olmayan səhifə `404`-ə gedir. Məna daşımayan parametr və cursor URL-ləri SEO yolu kimi təqdim olunmur.

Sıralama uyğunluq, son yayım və ID ilə sabitləşir. UTF-8 tokenləri locale üzrə normallaşdır və indekslə. Yapon və koreya mətnləri üçün boşluqla ayrılmış sözlərə güvənməyən sınaq, türk və Azərbaycan dillərində I/İ üçün ayrıca sınaq yaz. Axtarış mətnini analitika parametrinə, referrer, cache açarına və tətbiq loguna ötürmə. Axtarış nəticələrində yalnız cari locale və `public` versiyalar qalır. Telefon üzrə URL, fokus və geri düyməsi [UI müqaviləsinə](../ui/screens-and-states.md) tabedir.

İstinad: [Google Search Central, səhifələmə](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading)

[Sınaqlar: UI-02, SEO-01 və SEO-04](../delivery/acceptance.md)

HTML səhifələməsində NormalizeFeedPage middleware-i page parametrini müsbət tam ədəd kimi yoxlayır. page=1 digər query seçimlərini saxlayaraq əsas URL-ə 301 qaytarır. Paginatorun son səhifəsindən yuxarı səhifələr 404 alır. Sabit keyset/snapshot və həftəlik xal kontraktının tətbiqi LAV-019 (#44) üzrə açıqdır.
