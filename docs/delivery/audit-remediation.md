# 20 noyabr auditinin düzəlişləri

## Birinci paket — istifadəçi yolları (LAV-020)

Qəbul: LOC-01, SEO-01, SEO-04, UI-02. 17 dildə faktiki lent linki detail 200, doğru HTML dili və canonical verir; həmin URL sitemap-da olur. PT-BR başlığı fallback-sizdir. Şikayət forması da həmin məzmun dilində açılır. Axtarış iki səhifə arasında POST ilə sorğunu qoruyur, URL/sessiyaya yazmır. Robots üçün tək runtime mənbə və slash-sız sitemap ana URL-i var.

Yerli sübut: composer test — 78 test, 1029 assertion keçdi. AuditUserPathsTest real kart linklərini DOM-dan götürür. Test APP_URL-i localhost-a bağlanıb ki, lokal development domeni nəticəni dəyişməsin. Apache/staging xarici HTTP yoxlaması hələ edilməyib; hosting istifadəçi qərarı ilə təxirə salınıb.

## Sonrakı paketlər

- 17 dilin hesab, iştirak, şikayət və bərpa ekranlarına hazırlanmış mətnlərin inteqrasiyası; sahibin yekun dil baxışı ayrıca qalır.
- Lent snapshot xərci, rəylərin səhifələnməsi və sitemap sorğu/yaddaş yükünün məhdudlaşdırılması; sintetik yük ölçmələri.
- Ayrı reklamsız rəy forması, hüquqi səhifələrin əlçatanlığı və sessiya məxfiliyi.
- Real cihazlar, hüquqi operator məlumatları, backup bərpası və xarici reklam/hostinq yoxlamaları buraxılış qapıları olaraq qalır.
