# Cihaz və responsive sınaq protokolu

Bu sənəd UI-03, UI-04 və LOC-03 üçün real cihaz sınaqlarını qeyd etmək üçündür. Viewport emulyasiyası real iOS/Android, klaviatura, push provider və standalone rejiminin sübutu sayılmır. Hər sessiyada test edilən commit, cihaz modeli, OS build-i, brauzer versiyası, CSS viewport, ekran sıxlığı, locale, səhifə, tema və nəticəni yaz.

## Təhlükəsiz test məlumatı

- Yalnız lokal/staging bazada sintetik etiraf və test hesabı istifadə et. Real etiraf, e-poçt, bərpa kodu, endpoint tokeni, VAPID sirri və istifadəçi sessiyası screenshot və qeydə düşməsin.
- Yazı və şikayət formalarını yalnız bu məqsəd üçün ayrılmış sintetik qeydlə göndər. İstehsal məzmununu test etmə.
- Push icazəsi yalnız ayrılmış staging hesabı və test cihazında aktivləşdirilsin. Sınaq bitəndə abonementi tətbiqdən söndür.
- Hər screenshot-da URL və test kimliyi görünə bilər; göndərməzdən əvvəl şəxsi parametr və sirr olmadığını yoxla.

## Cihaz icra matrisi

| Cihaz və mühit | Minimum baxış | Qeyd |
|---|---|---|
| 320 CSS px dar görünüş | Home, topic, confession, write, account, offline | En daşması, toxunma sahələri, alt naviqasiya, uzun tərcümə |
| 390 CSS px telefon görünüşü | Yuxarıdakı əsas ekranlar və uzun form | Mətn yığılması, klaviatura, scroll və fokus |
| iPhone, Safari | 320/390-a uyğun mövcud cihaz; Safari versiyasını yaz | Klaviatura açıqkən fokuslu input görünür, yazı itmir, geri keçid işləyir |
| Android telefon, Chrome | 320/390-a uyğun mövcud cihaz; Android/Chrome versiyasını yaz | Klaviatura, sistem back, PWA install və opt-in push |
| Standalone PWA | Hər iki platformada ayrıca qeyd | Başlıq/safe-area, offline shell, geri qayıtma, update və push davranışı |
| Masaüstü brauzer | 1366 CSS px və real browser version | Header, oxu eni, keyboard/focus, day/night |

## Ssenari addımları

1. `docs/ui/wireframes.html` ilə ana, topic, confession, write, account, report və offline ekranlarını müqayisə et. Eyni route və locale üçün gündüz/gecə görünüşünü yoxla.
2. Yazı formasında synthetic mətn daxil et. Klaviatura görünəndə caret və xəta/status örtülməməlidir. Validation xətasından sonra mətn qalmalıdır. Back düyməsi route-u və mümkün olduqda scroll/fokus vəziyyətini bərpa etməlidir.
3. Tab/Shift+Tab ilə skip link, başlıq, əsas naviqasiya, tema, form və göndər düyməsini keç. Fokus vizual görünməli, modal/panel bağlananda açan elementə qaytarılmalıdır.
4. Ayrılmış fixture etirafında dəstək reaksiyasını ver və geri götür. Sintetik fixture-də şikayət formasını aç, göndəriş nəticəsini yoxla; production-da məlumat göndərmə.
5. PWA standalone rejimində şəbəkəni kəs. Cache-dən yalnız statik aktiv və locale offline shell görünməlidir. Etiraf, hesab, auth və form cavabı cache-dən qayıtmamalıdır. Offline `POST` serverə çatmamalıdır.
6. Hər 17 locale üçün ana, yazı və offline səhifədə ən azı 320 və 390 CSS px görünüşünü nəzərdən keçir. Uzun sətir, form etiketi, xəbərdarlıq və kateqoriya adı sınansın. Copy review `pending` locale təsdiqlənmiş sayılmır.

## Nəticə qeydi şablonu

```text
Date/time:
Commit / PR:
Device model:
OS version/build:
Browser and version:
Installed/standalone: yes/no
Viewport CSS px / DPR:
Locale and route:
Theme:
Scenario IDs:
Result (pass/fail/not run):
Observed behavior:
Screenshot file(s):
Follow-up issue:
```

## İlkin lokal nəticə · 2026-11-19

| Sahə | Sınaq mühiti və nəticə | Status |
|---|---|---|
| Locale responsive layout | Codex in-app desktop browser, local `127.0.0.1:8000`, `codex/lav-011-pwa-offline-push` commit `23ebd9b`; 17 locale × (`/`, `/write`, `/offline`) × 320/390/1366 CSS px = 153 route/viewport kombinasiyası. Hər səhifədə HTML və body scroll eni viewport-a bərabər idi. | Keçdi — emulyasiya |
| Cihaz qaralaması | Sintetik Azərbaycan dili mətni, razılıqdan sonra saxlandı və reload-dan sonra bərpa edildi; razılıq götürülüb yenidən açıldıqda mətni bərpa etmədi. | Keçdi — desktop browser |
| Tema və klaviatura fokusu | Day → night → day keçidi; Tab ilə skip link-dən brand link-ə görünən fokus keçidi. | Keçdi — desktop browser |
| iOS Safari / Android Chrome / standalone | Bu iş sessiyasında real cihaz və versiya əlçatan olmayıb. | İcra edilməyib |
| Klaviatura açıqkən caret, sistem back, support/report, real offline və push provider | Bu sessiyada tam cihaz axını və provider sınağı aparılmayıb. | İcra edilməyib |

Host macOS 27.2.1-dir; in-app browser-in dəqiq engine/versiyası sınaq alətində göstərilmir. Bu qeydin nəticəsi LAV-012-nin real cihaz qəbulunu bağlamır. CUA-dakı vizual baxış screenshot-ları sessiyada göründü, lakin repo daxilində cihaz sübutu kimi saxlanmayıb.
