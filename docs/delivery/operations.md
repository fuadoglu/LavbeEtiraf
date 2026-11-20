# Shared hosting, əməliyyat və buraxılış

## Faktiki təsdiq tələb edən qapılar

| Qapı | Məsul | Sübut |
|---|---|---|
| Hüquqi operator və qaydalar | Məhsul sahibi/operator | Təsdiqlənmiş hüquqi kimlik, əlaqə, məlumat axını, hosting lokasiyası, 18+ yanaşması və bazar tələbləri |
| Hosting | Development və operator | PHP CLI, MySQL/MariaDB, unikal açarlar, cron, HTTPS, SMTP, URL rewrite, açıq qovluq, izolə edilmiş backup/restore sınağı |
| 17 dil | Codex və məhsul sahibi | Paketlər tamamlanıb, sahib baxışı qeyd olunub, hər dil üçün real moderasiya və ilkin məzmun təmin edilib |
| Axtarış | Operator və development | Search Console, Bing, XML/dil auditləri və IndexNow sınaq hadisəsi |
| Ölçüm | Operator və development | İstehsal GA4 və CMP doğrulaması, şəbəkə məxfilik testi, consent rəddində davranış |
| AdSense aktivləşdirmə | Operator | Verified domen, uyğun consent, ads.txt və mobil yerləşim, Policy Center yoxlaması |
| Tam buraxılış | Məhsul sahibi | İşlədilmiş qəbul matrisi, real shared hosting smoke/yük, restore və rollback sübutu |

Məhsul bütün əsas funksiyalarla reklamsız işə hazır ola bilər. AdSense-in xarici təsdiqi reklam açarından asılı buraxılış əməliyyatıdır. Şəxsi operator qeydləri və real xidmət məlumatı açıq repoda saxlanmır. Əsas seçim Aİ regionunda məlumatın yeri aydın olan shared hostingdir. Faktiki provayder təsdiqlənənədək istehsal qabiliyyəti barədə iddia yoxdur.

## İstehsal quraşdırması

1. Staging və istehsalın DB, mail, domain və GA mühitlərini ayır. Staging-i girişlə qoru. İstehsal sirləri repo və build loguna düşməsin.
2. Artifact-i lock faylından hazırla, PHP uzantılarını və versiyanı yoxla, asset-ləri əvvəlcədən build et. Document root `public/` olur. Əvvəl staging miqrasiya və backup testindən keç.
3. Backup və undo planını yoxla. Son DB dəyişikliklərini geriyə uyğun apar. Miqrasiyadan sonra app health, HTTPS, cron kilidi, queue, səhifə dili, yazı, moderator axını və sitemap smoke testini işlə.
4. Yük sınağında eyni vaxtda oxu, yazı, rəy, sitemap və batch cron işləsin. CPU, DB və hostinq proses limiti məlum olduqda nəticə və qəbul edilən trafik ssenarisini qeyd et. Real resurs sayı olmadan tutum vəd etmə.
5. Sürət və məxfilik yoxlamasından sonra xarici aktivləşdirmələri mərhələli aç. Əsas oxu, yazı, moderasiya və SEO qanuni buraxılış qapılarından keçir.

LAV-009-un izlənən etiraf bildirişləri və LAV-014 IndexNow bildirişləri Laravel scheduler vasitəsilə qısa iş kimi çatdırılır; production-da daimi worker tələb olunmur. Hosting cron-u hər dəqiqə `php /path/to/app/artisan schedule:run` çağırmalıdır. `lavbe:deliver-notifications` participation outbox-u user/event üzrə unikallaşdırır, in-app qeydlər yaradır və yalnız təsdiqlənmiş, e-poçt opt-in etmiş hesab üçün ümumi mətnli məktub göndərir. Mühit mail provider-i qurmadan komanda `MAIL_MAILER=array` və ya `log` ilə smoke test edilə bilər; real SMTP aktivləşdirməsi ayrıca səlahiyyət və staging çatdırılma sınağı tələb edir. `lavbe:indexnow` yalnız moderasiya outbox-dakı etirafın public URL dəyişikliklərini göndərir; key/HTTPS host konfiqurasiyası yoxdursa deaktiv qalır, müvəqqəti xətaları məhdud artan fasilə ilə təkrar edir. Uğursuz göndərişdə mətn deyil, sabit xəta kodu və cəhd sayı qalır.

### PWA və Web Push

PWA origin-i HTTPS olmalı, `public/` document root, `storage/` yazıla bilən, `public/build/manifest.json` və versiyalı build aktivləri deployment artifact-də olmalıdır. Deploy zamanı `npm run build` development kompüterində/CI-də işləyir; production Node prosesi lazım deyil. Service worker yalnız statik versiyalı aktivləri və locale offline shell-i saxlayır; yeni build fingerprint-i əvvəlki static cache-i təmizləyir.

Push istifadə olunacaqsa hostinqdə PHP cURL, OpenSSL və mbstring uzantılarını yoxla, Composer dependency-lərini production artifact-ə daxil et və `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` dəyərlərini secret manager və ya hostinqin qorunan environment panelindən ver. Private key repo, frontend, log və deploy artefaktına daxil edilmir. Production-da eyni dəqiqəlik `schedule:run` cron-u `lavbe:deliver-web-push` işini də başladır; əl ilə `php artisan lavbe:deliver-web-push` ilə konfiqurasiya olmayan testdə komanda göndəriş etmədən dayanmalıdır. Konfiqurasiya açıldıqdan sonra əvvəl staging abonementi ilə qısa smoke test apar. İstifadəçi browser-i, OS-i və push provider-i üzrə real cihaz qəbul sınağı LAV-012/LAV-015-də ayrıca qeyd olunur.

## Rollback və insident

- Köhnə kod və asset-lər üçün geri dönüş versiyası saxla. Geriyə uyğun olmayan DB əməliyyatını əvvəlcədən planlaşdır. Backup yenidən açılmazdan əvvəl silinmə/gizlətmə jurnalı tətbiq olunur. Bərpa testini izolə mühitdə apar.
- Şəxsi məlumat ifşası zamanı səlahiyyətli moderator dərhal gizlədir. Açıq HTML, XML, preview və cache yoxlanır. Uyğun olduqda axtarış platformasının silmə kanalı ilə əməliyyat aparılır.
- AdSense riski yarananda qlobal reklam açarı söndürülür və keş yenilənir. Məxfilik və təhlükəsizlik insidenti operator proseduruna ötürülür.
- Cron və göndəriş növbəsinin son uğuru, gecikmiş şikayətin yaşı, sitemap uyğunsuzluğu, 5xx və backup sağlamlığı yoxlanılır. Sessiya və mətn loglanmır.

## Buraxılış nəticəsinin formatı

Hər qapı üçün bir qeyd saxlanır: mühit, Git SHA, sınaq vaxtı, məsul şəxs, nəticə, sübut və qalan risk. Faktiki operator, DNS, hosting limiti, reklam hesabı və qanuni təsdiqlər yalnız səlahiyyətli şəxsdən gəlir. Bu məlumat olmadan development sınaqları sintetik mühitdə işləyir. İctimai domen və reklam açarları öz təsdiq qapılarına tabedir.

| Axın | Konkret sübut |
|---|---|
| Məxfilik | İki ayrı qonaq və moderator hesabı ilə sahiblik izolyasiyası, reklamlı detal → rəy formu, razılığın geri alınması və tam şəbəkə izi |
| Yayım və silmə | Əvvəlki backup-dan izolə bərpa. Silinmə/gizlətmə jurnalı replay. Etiraf URL, preview, fragment, lent və XML təkrar açılmır. |
| Locale və moderator | 17 paket üzrə versiyalı sahib baxışı, ölkə/dil fallback sınağı, növbə üzrə məsul və əvəzləyici, təcili şikayətdə real marşrut |
| Cron və xarici sistem | İcazəli interval, uğursuzluq/retry, gecikmiş hadisənin yaşı və eyni bildirişin yalnız bir dəfə nəticə yaratması |
| Performans | Staging-də mobil cihaz, reklamsız və uyğun reklam simulyasiyası, oxu/yazı/cron paralel sınağı. Sahə ölçümü mövcuddursa ayrıca qeyd olunur. |

Backup məqsədi üçün ilkin RPO 24 saat, RTO 4 saatdır. Bunlar sınaqdan əvvəl xidmət vədi deyil. Faktiki qəbulda hostinqin bərpa vaxtı və silinmə jurnalının ayrılıqda qorunması ölçülür. Açıq platformanın gələcək texniki baxışında PHP/Laravel yeniləmələri və Google/hüquqi siyasət dəyişikliyi üçün operator məsuliyyəti saxlanır.
