# 20 noyabr auditinin düzəlişləri

## Birinci paket — istifadəçi yolları (LAV-020)

Qəbul: LOC-01, SEO-01, SEO-04, UI-02. 17 dildə faktiki lent linki detail 200, doğru HTML dili və canonical verir; həmin URL sitemap-da olur. PT-BR başlığı fallback-sizdir. Şikayət forması da həmin məzmun dilində açılır. Axtarış iki səhifə arasında POST ilə sorğunu qoruyur, URL/sessiyaya yazmır. Robots üçün tək runtime mənbə və slash-sız sitemap ana URL-i var.

Yerli sübut: composer test — 78 test, 1029 assertion keçdi. AuditUserPathsTest real kart linklərini DOM-dan götürür. Test APP_URL-i localhost-a bağlanıb ki, lokal development domeni nəticəni dəyişməsin. Apache/staging xarici HTTP yoxlaması hələ edilməyib; hosting istifadəçi qərarı ilə təxirə salınıb.

## Sonrakı paketlər

- 17 dilin hesab, iştirak, şikayət və bərpa ekranlarına hazırlanmış mətnlərin inteqrasiyası; sahibin yekun dil baxışı ayrıca qalır.
- Lent snapshot xərci, rəylərin səhifələnməsi və sitemap sorğu/yaddaş yükünün məhdudlaşdırılması; sintetik yük ölçmələri.
- Ayrı reklamsız rəy forması, hüquqi səhifələrin əlçatanlığı və sessiya məxfiliyi.
- Real cihazlar, hüquqi operator məlumatları, backup bərpası və xarici reklam/hostinq yoxlamaları buraxılış qapıları olaraq qalır.

## Sitemap yükü — LAV-022

SEO-01/SEO-02: kateqoriya alternativləri üçün dil+mövzu üzrə tək qruplaşdırılmış COUNT istifadə edilir. İlk sitemap hissəsində cəmi iki say sorğusu var: hissələrin sayı və kateqoriya sayları. Sonrakı hissələr kateqoriya saymır. XML etirafları config/seo.php sitemap_read_batch ilə 500-lük paketlərdə oxuyur; ilk offset-dən sonra ID keyset davamı işləyir. 49 000 sətrin hamısı PHP yaddaşına götürülmür. Hər paket cari public/approved scope-dan gəlir. SitemapScaleTest kiçik batch konfiqurasiyası ilə sorğu limitini, kateqoriya hreflang-larını və hissələr arasında təkrarsız tam bölgünü yoxlayır. Real hosting pik yaddaş və p95 ölçməsi hələ keçirilməyib.
## İkinci paket — runtime dil inteqrasiyası (LAV-021)

LAV-010 native-copy branch-ində hazırlanmış runtime PHP və mail JSON mətnləri cari main-ə daşınır. Hesab, iştirak, şikayət, moderasiya, validasiya və status paketləri 17 dildə fallback-siz yoxlanır. Yazı hintində konfiqurasiya limiti göstərilir; boş error hint mətnini gizlətmir. Naməlum route-un 404 ekranı URL dilini saxlayır. Native/English ad və SVG bayraqlar saxlanır, sabit dil axtarış placeholder-i çıxarılır. Sahibin yekun dil baxışı pending qalır; hüquqi mətn və real operator təsdiqi verilmir.

LAV-021 yerli sübut: composer test — 80 test / 19 117 assertion keçdi. LocaleCopyTest hər runtime açarı, placeholder və mail JSON açarını fallback olmadan yoxlayır; 17 dildə yazı, giriş, şikayət, bərpa və 404 ekranları render edilir. Sənəd validatoru da qovluq üçün URL seqmenti əvəzinə kataloqdakı content code istifadə edir.

## Rəy səhifələməsi — LAV-023

MOD-03 və UI-02: etiraf detalında rəylər konfiqurasiyadakı 20-lik HTML səhifələrlə göstərilir. Növbəti/Geri keçidləri JavaScript tələb etmir və rəylər başlığına qayıdır. Yalnız həmin səhifənin təsdiqli revision, müəllif cavabı və sahiblik məlumatı yüklənir. Cari public scope və private/no-store qorunur. CommentPaginationTest görünmə limiti, səhifələrarası keçid, gizlədilmiş rəyin çıxması və valideyn gizlədilməsini yoxlayır. Müəllif davamlarının yüklənməsi və lent snapshot optimallaşdırması hələ ayrıca qalır.

## Lent snapshot həcmi — LAV-025

SEO-04/UI-02: ilk lent sorğusunda cache-ə yazılan üzvlər config/feed.php snapshot_max_members ilə məhdudlaşdırılır (standart 1000). Bütün arxiv nömrəli HTML səhifələrdə görünür; cursor-lu rahat davam seçilmiş axınla bitir. İlk səhifənin ümumi sayı ayrıca SQL COUNT-dan gəlir. FeedSessionTest azaldılmış limitlə cache ölçüsünü, fragment sonunu və HTML page 3 əlçatanlığını yoxlayır. Bu, PHP yaddaş və cache yazısı üçün sərhəd yaradır; SQL say sorğusu və real host cavab vaxtı ayrıca ölçülməlidir.

## Başlıqda axtarış — LAV-024

UI-01/UI-04: axtarış yeganə ikon kimi başlıqda dil və rejim idarələrinin yanındadır. Lent başlığından təkrarlanan axtarış düyməsi çıxarılıb. Axtarışın öz POST forması və no-store davranışı saxlanır. UiFoundationTest ana, mövzu və axtarış ekranlarında tək başlıq ikonunu yoxlayır.

## Ayrıca reklamsız rəy ekranı — LAV-027

ADV-03/MOD-03: etiraf detalı rəy yazmaq üçün ayrıca `/{locale}/confession/{id}/comment` ekranına keçid verir. Ekran private/no-store, noindex, no-referrer və reklam səthindən kənardır. Mövcud POST endpoint-i və pending-only moderasiya dəyişmir. Bağlı rəylərdə forma gizlənir; gizli valideyn 404-dür. SeparateCommentFormTest bu davranışı yoxlayır.
