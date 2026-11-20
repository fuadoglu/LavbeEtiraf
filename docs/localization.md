# Lokalizasiya və kateqoriyalar

Codex bütün 17 dil paketini yazır. Məhsul sahibi yaradılmış mətnlərin yekun baxışını keçirir. Baxış ekranlara görə qruplaşdırılmış müqayisə və versiya diff-i ilə aparılır. Hüquqi mətn yalnız təsdiqlənmiş faktik operator və məlumat axını əsasında aktivləşir. Etiraflar orijinal dilində qalır.

| Paket | URL | Yerli ad | English label | Bayraq |
|---|---|---|---|---|
| en | en | English | English | GB |
| de | de | Deutsch | German | DE |
| fr | fr | Français | French | FR |
| nl | nl | Nederlands | Dutch | NL |
| sv | sv | Svenska | Swedish | SE |
| nb | nb | Norsk bokmål | Norwegian Bokmål | NO |
| da | da | Dansk | Danish | DK |
| fi | fi | Suomi | Finnish | FI |
| ja | ja | 日本語 | Japanese | JP |
| ko | ko | 한국어 | Korean | KR |
| es | es | Español | Spanish | ES |
| it | it | Italiano | Italian | IT |
| pt-BR | pt-br | Português do Brasil | Brazilian Portuguese | BR |
| pl | pl | Polski | Polish | PL |
| tr | tr | Türkçe | Turkish | TR |
| az | az | Azərbaycan dili | Azerbaijani | AZ |
| ru | ru | Русский | Russian | RU |

Yadda saxlanmış seçim → brauzerin dəstəklənən dil üstünlüyü → müəyyən ölkə siqnalı → ingilis dili. Ölkə siqnalı ilk girişdə köməkçidir və GPS tələb etmir. Çoxdilli ölkə üçün aydın seçim yoxdursa en istifadə edilir. Dəqiq locale uyğunluğu, sonra ana dil və reyestr aliası yoxlanır. `pt-PT` dəstəklənən portuqalca lentə, `no` Norveç Bokmål lentinə düşür. Ölkə üçün sabit fallback xəritəsi kataloqdadır. Yalnız kök giriş dili avtomatik seçir, açıq lokal URL eyni dili saxlayır. Əl ilə dəyişiklik yeni üstünlüyü saxlayır. Kateqoriyada analoji kateqoriyaya, etirafda seçilən dilin lentinə keçilir. Yazı açıqdırsa mətn, mövzu və etirafın dili qorunur.

Dil seçici ilk ekranda cari dili işarələyir. Axtarış native ad, ingiliscə ad, paket və URL kodu, normallaşdırılmış hərflər və paketdəki ləqəblər üzrə işləyir. İngilis adlarına görə stabil sıra. Məsələn, rus, рус, Russian, ru və turkce, Türkçe, tr ayrıca yoxlanır.

## Kateqoriyaların sabit xəritəsi

| Açar | Azərbaycanca | English | Lucide |
|---|---|---|---|
| relationships | Sevgi və münasibətlər | Love & Relationships | heart-handshake |
| family | Ailə | Family | house |
| friends | Dostluq və sosial həyat | Friends & Social Life | users-round |
| work | İş və karyera | Work & Career | briefcase-business |
| education | Təhsil | Education | graduation-cap |
| money | Pul və maliyyə | Money & Finances | wallet |
| identity | Özüm və kimliyim | Self & Identity | user-round |
| values | İnanc və dəyərlər | Beliefs & Values | compass |
| wellbeing | Sağlamlıq və rifah | Health & Wellbeing | heart-pulse |
| grief | İtki və yas | Loss & Grief | flower-2 |
| life-changes | Həyat dəyişiklikləri | Life Changes | route |
| everyday | Gündəlik həyat | Everyday Life | calendar-days |

Etirafın əsas mövzusu seçilir. Borc əsasdırsa money, etibar əsasdırsa relationships. Köç və uyğunlaşma əsasdırsa life-changes. Everyday gündəlik vərdiş və hadisələr üçündür. Kateqoriyasız göndəriş ayrıca baxış vəziyyətidir. Hər paket kateqoriyanın qısa adı, izahı və unikal URL hissəsini verir.

## Paket müqaviləsi

Bir reyestr dil kodu, URL seqmenti, native ad, ingilis adı, bayraq, yazı istiqaməti, axtarış ləqəbləri və reklam dili uyğunluğunu saxlayır. Paketlər ümumi açar müqaviləsinə uyur: interfeys, kateqoriyalar, şikayət və moderasiya, e-poçt, bildiriş, PWA, kömək, hüquqi mətn və SEO. Hər paketdə açar tamlığı, placeholder uyğunluğu, boş dəyərlər və slug unikallığı test edilir. Eyni məna, qısa səmimi cümlələr, həssas mətnə təzyiqsiz müraciət. Buraxılışda səssiz ingilis dili fallback-i çatışmayan mətni gizlətməməlidir. Yeni paket əlavə etməklə reyestr, dil seçici, lent, moderasiya filtri və sitemap idarə olunur.

## Maşınla yoxlanılan dil müqaviləsi

[Kateqoriya və dil kataloqu](../spec/catalog.json) 17 locale və 204 kateqoriya adı üçün başlanğıc redaktə variantını saxlayır. İkon və kateqoriya açarı sabitdir. Kateqoriya tərcümələri draft statusundadır. [Açar müqaviləsi](../spec/locale-contract.json) məcburi UI qruplarını, placeholder və cəm qaydasını, kateqoriya və hüquqi səhifə sahələrini açıq müəyyən edir.

Tam dil paketi `resources/lavbe/locales/<segment>.json` yolunda saxlanır. Kanonik ingilis mətnləri `spec/locale-messages.en.json` faylındadır; struktur və placeholder-lar `spec/locale-contract.json` ilə yoxlanır. Tətbiq ekranları və bildirişlər Laravel-in `resources/lang/<code>/` lüğətlərindən və `resources/lang/<code>.json` məktub mətnlərindən oxunur. URL-də `pt-br`, Laravel lüğətində `pt-BR` işlənir. Ekran mətni, düymə adı və əlçatanlıq etiketi üçün tərcümə açarı istifadə et. Yeni açarı bütün 17 dilə əlavə et. `LocaleCopyTest` açarları ehtiyat dilə keçmədən yoxlayır, dəyişənləri müqayisə edir və əsas ekranları hər dildə açır.

Hüquqi səhifələrin provisional mətni `draft` statusundadır. `approved` real hüquqi baxış, operator və məlumat axını təsdiqinə əsaslanır. Məzmun mətn formatında saxlanır, təhlükəsiz formatlanır. Təsdiq qeydi hüquqi şəxs və əlaqə detalları kimi daxili faktları lazımsız yerə repoya çıxarmamalıdır.

Hər dilin redaktə mənbəyi `spec/locale-drafts/<segment>.json` faylında `locale`, `version`, tam `messages`, 12 `category_descriptions` və ümumi `legal_body` sahələrini saxlayır. Məhsul sahibi bütün mətnləri yekun baxışdan keçirir; həminədək legal status `draft`, baxış statusu `pending` qalır. `python3 scripts/build_locale_packages.py` yalnız 17 draft tam olduqda paketləri qurur; `--only en` kimi seçim draft hazırlayarkən bir paketi yarada bilər. Paketlər `resources/lavbe/locales/<segment>.json` fayllarına yazılır; 12 kateqoriya slug-u hər dilin adından ASCII-yə transliterasiya edilir (yapon/koreya üçün romanizasiya xəritəsi işlənir). `python3 scripts/check_docs.py --packages resources/lavbe/locales` açar, placeholder, plural, unikal slug və hüquqi statusu yoxlayır.

Baxış preview-u `python3 scripts/check_locale_previews.py --chromium /path/to/chrome` ilə yaradılır. `storage/app/locale-review/preview.html` bütün 17 paketin mesajı, kateqoriya və hüquqi draftını göstərir; Chromium 320, 390 və 1366 CSS px-də horizontal overflow-u yoxlayır. Bu lokal content-layout smoke testidir, cihaz baxışını və məhsul sahibinin tərcümə təsdiqini əvəz etmir.

Sahib baxışı üçün `python3 scripts/export_locale_review.py --base <git-ref>` locale/group/key üzrə yalnız dəyişmiş sətirləri CSV-ə, `locale, package_version, git_ref, status` baxış qeydini yanaşı JSON-a çıxarır. Baxış statusu `pending` ilə başlayır. Son qərarı məhsul sahibi verir; Codex həmin statusu təsdiq kimi dəyişmir. Açılış üçün 17 təsdiqlənmiş paket, vizual sınaq, ayrıca 17 dil üzrə mövcud moderator və real başlanğıc məzmunu tələb olunur.

Yoxlama: `python3 scripts/check_docs.py` spesifikasiyanı, `python3 scripts/check_docs.py --packages resources/lavbe/locales` yaradılmış paketləri yoxlayır. Tətbiqdə 17 paket yaradılanadək ikinci əmr buraxılış backlog tapşırığıdır.
