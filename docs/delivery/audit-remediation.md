# 20 noyabr auditinin düzəlişləri

## Birinci paket — istifadəçi yolları (LAV-020)

Qəbul: LOC-01, SEO-01, SEO-04, UI-02. 17 dildə faktiki lent linki detail 200, doğru HTML dili və canonical verir; həmin URL sitemap-da olur. PT-BR başlığı fallback-sizdir. Şikayət forması da həmin məzmun dilində açılır. Axtarış iki səhifə arasında POST ilə sorğunu qoruyur, URL/sessiyaya yazmır. Robots üçün tək runtime mənbə və slash-sız sitemap ana URL-i var.

Yerli sübut: composer test — 78 test, 1029 assertion keçdi. AuditUserPathsTest real kart linklərini DOM-dan götürür. Test APP_URL-i localhost-a bağlanıb ki, lokal development domeni nəticəni dəyişməsin. Apache/staging xarici HTTP yoxlaması hələ edilməyib; hosting istifadəçi qərarı ilə təxirə salınıb.

## Sonrakı paketlər

- 17 dilin hesab, iştirak, şikayət və bərpa ekranlarına hazırlanmış mətnlərin inteqrasiyası; sahibin yekun dil baxışı ayrıca qalır.
- Lent snapshot xərci, rəylərin səhifələnməsi və sitemap sorğu/yaddaş yükünün məhdudlaşdırılması; sintetik yük ölçmələri.
- Ayrı reklamsız rəy forması, hüquqi səhifələrin əlçatanlığı və sessiya məxfiliyi.
- Real cihazlar, hüquqi operator məlumatları, backup bərpası və xarici reklam/hostinq yoxlamaları buraxılış qapıları olaraq qalır.

## İkinci paket — runtime dil inteqrasiyası (LAV-021)

LAV-010 native-copy branch-ində hazırlanmış runtime PHP və mail JSON mətnləri cari main-ə daşınır. Hesab, iştirak, şikayət, moderasiya, validasiya və status paketləri 17 dildə fallback-siz yoxlanır. Yazı hintində konfiqurasiya limiti göstərilir; boş error hint mətnini gizlətmir. Naməlum route-un 404 ekranı URL dilini saxlayır. Native/English ad və SVG bayraqlar saxlanır, sabit dil axtarış placeholder-i çıxarılır. Sahibin yekun dil baxışı pending qalır; hüquqi mətn və real operator təsdiqi verilmir.

LAV-021 yerli sübut: composer test — 80 test / 19 117 assertion keçdi. LocaleCopyTest hər runtime açarı, placeholder və mail JSON açarını fallback olmadan yoxlayır; 17 dildə yazı, giriş, şikayət, bərpa və 404 ekranları render edilir. Sənəd validatoru da qovluq üçün URL seqmenti əvəzinə kataloqdakı content code istifadə edir.

## Rəy səhifələməsi — LAV-023

MOD-03 və UI-02: etiraf detalında rəylər konfiqurasiyadakı 20-lik HTML səhifələrlə göstərilir. Növbəti/Geri keçidləri JavaScript tələb etmir və rəylər başlığına qayıdır. Yalnız həmin səhifənin təsdiqli revision, müəllif cavabı və sahiblik məlumatı yüklənir. Cari public scope və private/no-store qorunur. CommentPaginationTest görünmə limiti, səhifələrarası keçid, gizlədilmiş rəyin çıxması və valideyn gizlədilməsini yoxlayır. Müəllif davamlarının yüklənməsi və lent snapshot optimallaşdırması hələ ayrıca qalır.
