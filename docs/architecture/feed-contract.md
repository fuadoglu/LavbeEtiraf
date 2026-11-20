# Lent, səhifələmə və axtarış müqaviləsi

Bu sənəd ana və kateqoriya lentinin server, JavaScript və botlar üçün vahid davranışını müəyyən edir. Görünüş [ekran atlasındadır](../ui/screens-and-states.md).

## İki giriş, eyni açıq məzmun qaydası

| Giriş | Məqsəd | Müqavilə |
|---|---|---|
| Açıq HTML | İlkin render, JavaScript-siz keçid, botlar | `/{locale}/`, `/{locale}/topics/{slug}`, sonrakı səhifə `?page=2`. Hər səhifə 12 kart və növbəti əvvəlcədən tanınan HTML linki verir. |
| Davam fragmenti | Daha çox düyməsi və mövqe qorunması | `/{locale}/feed-fragment?cursor=…`. Server ilk HTML-də imzalı cursor verir. Hər cavab HTML fragment, növbəti URL və cursor, sona çatma göstəricisi verir. |
| Açıq axtarış forması | Həmin dildə təsdiqlənmiş etiraf tapmaq | `/{locale}/search`. Açılış GET-dir, axtarış mətni POST ilə ötürülür. İctimai cavab noindex, no-store, reklamsız, analitika sorğusu göndərmir. |

## Cari tətbiq

İlk HTML səhifəsi ilə fragmentlər eyni dondurulmuş üzvlük siyahısından gəlir. Siyahı cari approved/public sorğusundan bir dəfə götürülür. Hər üzv üçün yalnız etiraf ID-si, publication_sequence və ilkin yayım vaxtı cache-də saxlanır. Mətn, başlıq, sessiya və sahiblik məlumatı snapshot-a daxil edilmir. Cache üçün Laravel-in mövcud database/file store-u kifayətdir.

Cursor Laravel Crypt ilə autentifikasiya/şifrələmə olunur və locale, kateqoriya, order, snapshot ID-si, son mövqe/sort cütü, as_of, yuxarı yayım nömrəsi və ilkin bitmə vaxtına bağlanır. Yeni sıralamada son yayım vaxtı/ID cütündən əvvəlki üzvlər seçilir. Həftəlik və davam sırası dəyişsə də snapshot mövqeləri saxlanır. Sonrakı cursor ilkin TTL-i uzatmır. Cache qeydi itəndə və ya TTL bitəndə 410 qaytarılır. Köhnə v1 cursor da 410 ilə yeni sessiyaya yönəldilir.

Hər paket üzvləri kiçik qruplarla oxuyur və cari public scope, locale/kateqoriya, approved revision və publication_sequence uyğunluğunu yenidən yoxlayır. Gizli, silinmiş və yenidən public olmuş qeydlər keçilir. Boşluqlar növbəti uyğun üzvlərlə doldurulur. Növbəti mövqe baxılmış son snapshot mövqeyidir. Yeni dərc olunmuş qeyd mövcud snapshot-a daxil olmur. Snapshot üzvlüyü nömrəsi əvvəldən ayrılmış, amma ilk oxu zamanı hələ görünməyən tranzaksiyanı da kənarda saxlayır.

Səhifə 2 və sonrakı nömrəli HTML arxiv səhifələri cari query-ni istifadə edir. İlk səhifə ilə daha çox yükləmə sabit sessiyanı bölüşür. Axtarış eyni locale və public scope-da sadə LIKE istifadə edir. MySQL full-text/tokenizer işi ayrıca qalır.

Həftəlik xal config/feed.php üzrə son yeddi gündə aktiv dəstək üçün 1, təsdiqlənmiş unikal rəyçi üçün 2-dir. Unikal rəyçi eyni etiraf daxilində sabit anonim alias ilə sayılır. Köhnə etiraf da son yeddi gündə fəaliyyət aldıqda həftəlik sıraya yüksələ bilər. Davam sırası yalnız canlı approved davamı olan valideynləri göstərir.

Ayrılmış SQLite yaddaş bazasında 1 000 sintetik etiraf üçün snapshot metadata JSON ölçüsü 59 787 bayt, bir lokal capture ölçməsi 47,24 ms oldu. Bunlar lokal ölçmələrdir. Snapshot ölçüsü üzv sayı ilə artır. Production workload və real cache store performansı buraxılışdan əvvəl ayrıca ölçülür.

Hər iki lent eyni sorğuda `locale`, kateqoriya və cari açıq vəziyyəti yoxlayır. Açıq başlıq, snippet və kart yalnız təsdiqlənmiş versiyadan gəlir. Sıralama `new`, `weekly` və `updates` allowlist-dən gəlir. Əsas standart `new`-dur, axtarış motoruna təqdim olunan ardıcıl səhifələr də bu sıradadır.

## Dəqiq səhifələmə

- **Yeni:** `published_at DESC, id DESC`. İlk oxu üçün serverin `as_of` sərhədi, son kartın sort cütü, locale/kateqoriya və görünürlük ardıcıllığının yuxarı sərhədi cursor-a bağlıdır. Etiraf hər dəfə açıq lentə daxil olanda yeni monoton görünürlük nömrəsi alır. Sonrakı paket yalnız cütdən sonra gələn, cari açıq kartları oxuyur. Yeni və yenidən açılmış etiraflar ilkin sessiyaya girmir, görünməz edilən etiraflar isə dərhal çıxır.
- **Davamı gəldi** və **Bu həftə:** hər locale və filtr üçün sırası sabitlənmiş, versiyalı snapshot. Snapshot ID və son sıra nömrəsi cursor-a bağlıdır, hər paket göstərməzdən əvvəl həmin ID-lərin cari açıq vəziyyətini yoxlayır. Mövcud olmayan və ya gizli qeydi keç, sona qədər uyğun kartla doldur. Son sıra göstərilən son kartla deyil, baxılmış son snapshot mövqeyi ilə müəyyən edilir. Sıra bir buraxılışda yenilənsə də aktiv oxu sessiyasının snapshot-ı TTL müddətində qalır.
- Həftəlik xal son yeddi gün ərzində unikal dəstək üçün 1, təsdiqlənmiş unikal rəyçi üçün 2-dir. Xal bərabərdirsə yayım tarixi və ID eyni sıra yaradır. Davam sıralamasında son təsdiqlənmiş canlı davam və ID istifadə olunur. Snapshot və cursor ilkin olaraq 60 dəqiqə saxlanır. Səhifə yenilənəndə yeni sıra başlayır. Snapshot ölçüsü real DB yükündə ölçülür, məhdudiyyət olduqda interfeys seçilmiş axın kimi davranır, bütün etiraflar Yeni arxivindən əlçatan qalır.
- JavaScript-siz indekslənən səhifələr cari oxuna bilən arxivin görünüşüdür. Ayrı zamanlarda açılan nömrəli səhifələrdə arxiv dəyişə bilər. İstifadəçi üçün eyni oxu sessiyasındakı dəqiq davam cursor vasitəsilə verilir. JavaScript varsa `Daha çox` həmin cursor-u istifadə edir, adi link qüvvədə qalır. Yeni etirafın yayımını gecikdirmədən həm SEO arxivi, həm də sabit oxu davamı təmin olunur.

## Fragment cavabı və xəta

Cursor məzmun və ya istifadəçi məlumatı daşımır. Locale, order, kateqoriya açarı, snapshot/sərhəd, son mövqe, versiya və bitmə vaxtı imzalanır. Cursor başqa filtrə köçürüləndə, imza pozulanda və ya format səhvdirsə `400`. Bitmiş və ya itmiş snapshot `410` qaytarır, UI köhnə kartları saxlayıb **Lenti yenilə** göstərir. Keçici şəbəkə xətasında **Yenidən cəhd et** eyni cursor-la işləyir. Cavab yalnız əsas lentin cari sorğu ID-si ilə uyğun gələndə əlavə olunur. Təkrarlanan kart ID-si müştəridə də çıxarılır.

Fragment cavabı `{ html, next_url, next_cursor, has_more }` formasındadır. Son səhifədə hər iki next dəyəri null olur. Fragment endpoint `X-Robots-Tag: noindex` qaytarır, sitemap və canonical arxivə daxil olmur. Canonical HTML səhifələri öz URL-sinə işarə edir. `page=1` əsas URL-ə, mövcud olmayan səhifə `404`-ə gedir. Məna daşımayan parametr və cursor URL-ləri SEO yolu kimi təqdim olunmur.

Sıralama uyğunluq, son yayım və ID ilə sabitləşir. UTF-8 tokenləri locale üzrə normallaşdır və indekslə. Yapon və koreya mətnləri üçün boşluqla ayrılmış sözlərə güvənməyən sınaq, türk və Azərbaycan dillərində I/İ üçün ayrıca sınaq yaz. Axtarış mətnini analitika parametrinə, referrer, cache açarına və tətbiq loguna ötürmə. Axtarış nəticələrində yalnız cari locale və `public` versiyalar qalır. Telefon üzrə URL, fokus və geri düyməsi [UI müqaviləsinə](../ui/screens-and-states.md) tabedir.

İstinad: [Google Search Central, səhifələmə](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading)

[Sınaqlar: UI-02, SEO-01 və SEO-04](../delivery/acceptance.md)

LAV-019-un yayım ardıcıllığı hissəsində publication_sequence etirafın public vəziyyətinə hər girişində feed_publications sayğacından ayrılır. Public vəziyyətdə redaktə və uşaq materialına görə visibility_version dəyişməsi bu nömrəni dəyişmir. Mövcud public qeydlərə migration zamanı nömrə verilir. Bu metadata mətn və istifadəçi identifikatoru daşımır. Model ilə yayım eyni DB tranzaksiyasında ediləndə sayğac və status birlikdə rollback olunur. Sequence nömrəsi snapshot üzvlüyü ilə birlikdə cursor davamında yoxlanır.

Database cache istifadə ediləndə vaxtı bitmiş snapshot qeydlərinin yığılmaması üçün lavbe:prune-expired-cache gündəlik scheduler-ə əlavə edilib. Əmr yalnız aktiv database cache store-un vaxtı bitmiş qeydlərini silir. Store database deyilsə dəyişiklik etmir. Mövcud cron schedule:run çağırışı bu işi də icra edir.

HTML page=1 digər filtr seçimlərini saxlayaraq 301 ilə əsas URL-ə qayıdır. Yanlış və mövcud olmayan nömrəli səhifələr 404 qaytarır.

LAV-020 audit düzəlişi: axtarış yalnız POST mətnini işləyir. Növbəti/Geri idarələri CSRF-li HTML POST formalarıdır; eyni sorğu və səhifə nömrəsi form body-də ötürülür. Sorğu URL-ə və sessiyaya yazılmır, cavab private/no-store və no-referrer qalır. Dil reyestrinin contentCode() nəticəsi bazada, urlSegment() nəticəsi keçidlərdə işlənir; pt-BR məzmunu pt-br URL-lərində açılır. Sitemap ana ünvanları canonical kimi son slash olmadan verilir. robots.txt yalnız Laravel marşrutundan gəlir; public qovluğunda onu kölgələyən fayl yoxdur.
