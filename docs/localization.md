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

Dil seçici ilk ekranda cari dili işarələyir. Desktop header-də bayraqla yanaşı dilin native adı tam göstərilir, qısa locale kodu göstərilmir; mobil header-də yalnız bayraq görünür və native ad əlçatan ad kimi saxlanır. Açılan menyuda bayraq, native ad və ingiliscə ad görünür. Axtarış native ad, ingiliscə ad, paket və URL kodu, normallaşdırılmış hərflər və paketdəki ləqəblər üzrə işləyir. İngilis adlarına görə stabil sıra. Məsələn, rus, рус, Russian, ru və turkce, Türkçe, tr ayrıca yoxlanır.

## Kateqoriyaların sabit xəritəsi

| Açar | Azərbaycanca | English | Məna (AZ) | Lucide |
|---|---|---|---|---|
| relationships | Sevgi və münasibətlər | Love & Relationships | Gizli sevgi, evlilik, yaxınlıq, qısqanclıq, xəyanət və ayrılıq. | heart-handshake |
| family | Ailə arasında | Family Ties | Valideyn, bacı-qardaş və qohumlarla münasibət, ailə sirri, borcluluq hissi və şəxsi sərhədlər. | house |
| friends | Dostluq və etibar | Friendship & Trust | Dost sirri, paxıllıq, birtərəfli dostluq, çevrədən kənarda qalmaq və böyüdükcə yadlaşmaq. | users-round |
| money | Pul və dolanışıq | Money & Making Ends Meet | Borc, gizli xərclər, maaş fərqi, ailəyə maddi kömək və pulun münasibətlərə təsiri. | wallet |
| work | İşdə başıma gələnlər | Things That Happen at Work | Rəhbər və həmkarlar, haqsızlıq, səhv, ambisiya, işsizlik və uğurun görünməyən tərəfi. | briefcase-business |
| identity | Özümə də deyə bilmədiklərim | Things I Can’t Even Tell Myself | Adını qoymaq çətin olan hisslər, kimlik, tənhalıq, özünəinam və daxili ziddiyyətlər. | fingerprint |
| grief | Valideyn olmaq | Being a Parent | Övlad böyütmək, valideyn olmaq istəyi və tərəddüdü, səbir, məsuliyyət və öz uşaqlığından qalanlar. | baby |
| education | Məktəb və tələbə həyatı | School & Student Life | İmtahan, müəllim, ixtisas seçimi, uğursuzluq qorxusu və tələbəlikdə aidiyyət hissi. | graduation-cap |
| wellbeing | Bədənim və sağlamlığım | My Body & Health | Görünüş, xəstəlik, fiziki və psixoloji sağlamlıq, başqalarının görə bilmədiyi çətinliklər. | heart-pulse |
| everyday | Aramızda qalsın | Between Us | Gündəlik həyatın xırda sirləri, gülməli vərdişlər, yöndəmsiz anlar və gözlənilməz yaxşılıqlar. | coffee |
| values | Vicdan və inanclar | Conscience & Beliefs | Doğru bildiyinlə etdiyin arasındakı fərq, din, ənənə, bağışlamaq, susmaq və sözünü demək. | compass |
| life-changes | İtirdiklərim və yeni başlanğıclar | Loss & New Beginnings | Yas, həsrət, köç, geridə qalan həyat və ayrılıqdan sonra özünə yer tapmaq. | signpost |

Etirafın əsas mövzusu seçilir. Borc və dolanışıq əsasdırsa `money`, dostluqdakı etibar və kənarda qalmaq əsasdırsa `friends`, romantik yaxınlıq və ayrılıq əsasdırsa `relationships` seçilir. Valideynlik və övlad böyütmək `grief` sabit daxili açarı altında, yas, həsrət, köç və yeni həyata uyğunlaşma isə `life-changes` altında yerləşir. `grief` açarı URL və tətbiq müqaviləsi sabit qalsın deyə saxlanmış tarixi texniki açardır; istifadəçiyə valideynlik adı göstərilir. Mövcud `grief` etirafları `life-changes` kateqoriyasına bir dəfəlik migration ilə keçir. Digər kateqoriyalar istifadəçinin verdiyi yeni mövzu sərhədlərinə uyğun olaraq eyni açarla davam edir.

## Paket müqaviləsi

Bir reyestr dil kodu, URL seqmenti, native ad, ingilis adı, bayraq, yazı istiqaməti, axtarış ləqəbləri və reklam dili uyğunluğunu saxlayır. Paketlər ümumi açar müqaviləsinə uyur: interfeys, kateqoriyalar, şikayət və moderasiya, e-poçt, bildiriş, PWA, kömək, hüquqi mətn və SEO. Hər paketdə açar tamlığı, placeholder uyğunluğu, boş dəyərlər və slug unikallığı test edilir. Eyni məna, qısa səmimi cümlələr, həssas mətnə təzyiqsiz müraciət. Buraxılışda səssiz ingilis dili fallback-i çatışmayan mətni gizlətməməlidir. Yeni paket əlavə etməklə reyestr, dil seçici, lent, moderasiya filtri və sitemap idarə olunur.

## Maşınla yoxlanılan dil müqaviləsi

[Kateqoriya və dil kataloqu](../spec/catalog.json) 17 locale üzrə 204 kateqoriya adı və 204 mövzu izahının redaktə variantını saxlayır. İkon və kateqoriya açarı sabitdir. Kateqoriya tərcümələri draft statusundadır. [Açar müqaviləsi](../spec/locale-contract.json) məcburi UI qruplarını, placeholder və cəm qaydasını, kateqoriya və hüquqi səhifə sahələrini açıq müəyyən edir.

Tam dil paketi `resources/lavbe/locales/<segment>.json` yolunda saxlanır. Kanonik ingilis mətnləri `spec/locale-messages.en.json` faylındadır; struktur və placeholder-lar `spec/locale-contract.json` ilə yoxlanır. LAV-003 ana ekran üçün `resources/lang/<locale>/lavbe.php` fayllarında yalnız feed, kateqoriya, sıralama, səhifələmə və axtarış mətnlərini gətirir; bu məhdud açar dəsti tam paketlərin əvəzi deyil.

Hüquqi səhifələrin provisional mətni `draft` statusundadır. `approved` real hüquqi baxış, operator və məlumat axını təsdiqinə əsaslanır. Məzmun mətn formatında saxlanır, təhlükəsiz formatlanır. Təsdiq qeydi hüquqi şəxs və əlaqə detalları kimi daxili faktları lazımsız yerə repoya çıxarmamalıdır.

Hər locale üçün ilkin maşın tərcüməsi draftı `spec/locale-drafts/<segment>.json` faylında `locale`, `version`, tam `messages`, 12 `category_descriptions` və ümumi `legal_body` sahələrini saxlayır. Məhsul sahibi bütün mətnləri yekun baxışdan keçirir; həminədək legal status `draft`, baxış statusu `pending` qalır. `python3 scripts/build_locale_packages.py` yalnız 17 draft tam olduqda paketləri qurur; `--only en` kimi seçim draft hazırlayarkən bir paketi yarada bilər. Paketlər `resources/lavbe/locales/<segment>.json` fayllarına yazılır; 12 kateqoriya slug-u hər dilin adından ASCII-yə transliterasiya edilir (yapon/koreya üçün romanizasiya xəritəsi işlənir). `python3 scripts/check_docs.py --packages resources/lavbe/locales` açar, placeholder, plural, unikal slug və hüquqi statusu yoxlayır.

Baxış preview-u `python3 scripts/check_locale_previews.py --chromium /path/to/chrome` ilə yaradılır. `storage/app/locale-review/preview.html` bütün 17 paketin mesajı, kateqoriya və hüquqi draftını göstərir; Chromium 320, 390 və 1366 CSS px-də horizontal overflow-u yoxlayır. Bu lokal content-layout smoke testidir, cihaz baxışını və məhsul sahibinin tərcümə təsdiqini əvəz etmir.

Sahib baxışı üçün `python3 scripts/export_locale_review.py --base <git-ref>` locale/group/key üzrə yalnız dəyişmiş sətirləri CSV-ə, `locale, package_version, git_ref, status` baxış qeydini yanaşı JSON-a çıxarır. Baxış statusu `pending` ilə başlayır. Son qərarı məhsul sahibi verir; Codex həmin statusu təsdiq kimi dəyişmir. Açılış üçün 17 təsdiqlənmiş paket, vizual sınaq, ayrıca 17 dil üzrə mövcud moderator və real başlanğıc məzmunu tələb olunur.

Yoxlama: `python3 scripts/check_docs.py` spesifikasiyanı, `python3 scripts/check_docs.py --packages resources/lavbe/locales` yaradılmış paketləri yoxlayır. Tətbiqdə 17 paket yaradılanadək ikinci əmr buraxılış backlog tapşırığıdır.

### LAV-061 · Mətnlərin redaktəsi

Oxu, yazı, rəy, şikayət, hesab və bərpa ekranlarında istifadəçiyə hər dilin öz müraciət formasında xitab edilir. Başlıq məcburidir. Açıq yazı kənarda surətlənə və axtarışda görünə bilər. Hüquqi səhifələri real davranışla birlikdə yenilə. İstehsaldan əvvəl operatorun təsdiqlədiyi açıq hüquqi adı və nəzarət edilən poçt ünvanını LAVBE_OPERATOR_NAME və LAVBE_PRIVACY_EMAIL ilə qur. Hüquqi əsaslar, alıcılar, ötürmələr, məlumat növləri üzrə saxlama müddətləri və müvafiq bazarın tələbləri ayrıca təsdiqlənməlidir. Bu redaktə hüquqi və native sahib baxışını açıq saxlayır.
