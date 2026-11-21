# Lent, səhifələmə və axtarış müqaviləsi

Bu sənəd ana və kateqoriya lentinin server, brauzer və botlar üçün vahid davranışını müəyyən edir. Görünüş [ekran atlasındadır](../ui/screens-and-states.md).

## Tək lent naviqasiyası

Ana və kateqoriya lentləri ilkin HTML-də render olunur. Sonrakı səhifələr `?page=N` ilə adi nömrəli linklərdir; JavaScript söndürüləndə də işləyir. Hər səhifə `config/feed.php → page_size` həddində kart qaytarır. Səhifələmə cari locale, kateqoriya və sort sorğusunu saxlayır. `page=1` əsas URL-ə normallaşır, son səhifədən böyük nömrə 404 verir. Hər səhifə öz canonical URL-nə işarə edir; `new`-dən başqa sıralamalar və arxiv səhifələri `noindex,follow` olur.

Lentdə cursor/fragment API-si və sonsuz yükləmə yoxdur. Beləliklə, HTML səhifələmə yeganə naviqasiya mənbəyidir və hər sorğu cari public scope-u yenidən yoxlayır. Nömrəli səhifələr müxtəlif vaxtlarda açılanda arxiv dəyişə bilər: yeni məzmun dərc edilə, köhnəsi gizlədilə bilər. Bu, sabit oxu snapshot-ı deyil, həmişə aktual ictimai lentdir.

Ana və kateqoriya lentləri eyni `FeedQuery` read modelindən istifadə edir. Yalnız cari approved revision, uyğun locale, public status, yayımlanma vaxtı və kateqoriya üzrə nəticə verilir. Sabit tie-break eyni sort dəyərində nəticələri deterministik edir. Pagination dəstək, şərh və moderator siyahıları üçün ayrıca öz parametr adlarını saxlayır.

## Sort qaydaları

İctimai sıralamalar `config/lavbe.php → feed_orders` siyahısından gəlir:

- **Yeni:** `published_at DESC`, sonra etiraf ID-si.
- **Bu həftə:** son `config/feed.php → weekly_days` günündə aktiv unikal dəstəklər və approved unikal rəyçilər çəkili xala çevrilir; standart çəkilər `support_weight` və `commenter_weight`-dir.
- **Məşhur etiraflar:** eyni xallar bütün tarix üzrə hesablanır.

Xal bərabərdirsə yayım vaxtı və ID sabit ardıcıllıq yaradır. Hər iki aqreqat sorğusu qruplaşmadan əvvəl istənən locale-in public etiraflarına və cari approved revision-a məhdudlaşır. Həftəlik pəncərə sərhədi `now()` əsasında hesablanır. `EXPLAIN ANALYZE`, istehsala yaxın məlumat həcmi və real hosting p95-i ayrıca release gate-dir; lokal testlər onların əvəzi sayılmır.

## Axtarış

Axtarış eyni locale-də təsdiqlənmiş public scope-u yoxlayır. Forma POST edir; axtarış mətni URL, səhifə mənbəyi, referrer və analitikaya düşmür. Nəticələr `noindex`, `private, no-store` qaytarır və reklam/analitika daşımır. `%`, `_` və escape işarəsi literal kimi işlənməlidir. Böyük kataloqda tam mətn indeksi, tokenizer keyfiyyəti və sürət üçün hostingə uyğun ayrıca benchmark qalır.

## SEO və görünürlük

Canonical HTML səhifələri öz nömrəli URL-sinə işarə edir. Feed fragment endpoint-i yoxdur. Sitemap yalnız canlı public etirafları göstərir. Geri çəkmə və silmə sorğudan dərhal sonra feed və XML-dən məzmunu çıxarır; kənar keş yalnız bunun origin-də yenidən yoxlanmasına zəmanət verərsə istifadə oluna bilər. İctimai etiraf detail-i iştirakçıya görə dəyişən hissələr ehtiva edə bilər və ümumi HTML CDN keşi almır.

**Əlaqəli qəbul:** UI-02, FEED-01, FEED-02, SEO-04, PAG-02.
