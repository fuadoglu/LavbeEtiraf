# Lokalizasiya və kateqoriyalar

Codex bütün 17 dil paketini yazıb. Məhsul sahibi 22 noyabr 2026-da 17 dil üzrə tərcümələrin yekun baxışının tamamlandığını təsdiqləyib. Bu qeyd `main`-dəki cari runtime mətnlərinə aiddir; sonrakı mətn dəyişiklikləri ayrıca yoxlanır. Hüquqi mətnin faktiki operator və məlumat axını üzrə təsdiqi dil baxışından ayrıdır. Etiraflar orijinal dilində qalır.

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

[Kateqoriya və dil kataloqu](../spec/catalog.json) 17 locale üzrə 204 kateqoriya adı və 204 mövzu izahını saxlayır. İkon və kateqoriya açarı sabitdir. Kateqoriya tərcümələri sahibin dil baxışına daxildir. [Açar müqaviləsi](../spec/locale-contract.json) məcburi UI qruplarını, placeholder və cəm qaydasını, kateqoriya və hüquqi səhifə sahələrini açıq müəyyən edir.

Cari tətbiq mətnləri `resources/lang/<locale>/` və `resources/lang/<locale>.json` fayllarından, kateqoriya adları isə `spec/catalog.json`-dan oxunur. Köhnə LAV-010 draftındakı `resources/lavbe/locales/<segment>.json` cari runtime mənbəyi deyil. 17 dilin interfeys, hüquqi səhifə və bildiriş mətnləri indiki mənbələrdə saxlanır.

Hüquqi səhifələrin provisional mətni `draft` statusundadır. `approved` real hüquqi baxış, operator və məlumat axını təsdiqinə əsaslanır. Məzmun mətn formatında saxlanır, təhlükəsiz formatlanır. Təsdiq qeydi hüquqi şəxs və əlaqə detalları kimi daxili faktları lazımsız yerə repoya çıxarmamalıdır.

Sahibin cari 17 dil üzrə təsdiqi bu sənəddə və LAV-010 issue-sunda qeyd olunur; köhnə draft PR-ın müqayisə ixracı cari tətbiqə daxil edilməyib. Gələcək mətn düzəlişləri üçün dəyişən açarlar PR diff-ində göstərilir və məhsul sahibi həmin dəyişikliklərə ayrıca baxır. İctimai açılış üçün dil baxışından əlavə real cihaz sınağı, dil üzrə moderator və uyğun ilkin məzmun tələb olunur.

Yoxlama: `python3 scripts/check_docs.py` 17 dilin cari mətn açarlarını və kataloqu yoxlayır. Dil baxışı bu avtomatik struktur yoxlamasından ayrıca məhsul sahibinin qərarıdır.

### LAV-061 · Mətnlərin redaktəsi

Oxu, yazı, rəy, şikayət, hesab və bərpa ekranlarında istifadəçiyə hər dilin öz müraciət formasında xitab edilir. Başlıq məcburidir. Açıq yazı kənarda surətlənə və axtarışda görünə bilər. Hüquqi səhifələri real davranışla birlikdə yenilə. İstehsaldan əvvəl operatorun təsdiqlədiyi açıq hüquqi adı və nəzarət edilən poçt ünvanını LAVBE_OPERATOR_NAME və LAVBE_PRIVACY_EMAIL ilə qur. Hüquqi əsaslar, alıcılar, ötürmələr, məlumat növləri üzrə saxlama müddətləri və müvafiq bazarın tələbləri ayrıca təsdiqlənməlidir. Bu faktiki hüquqi baxış tərcümələrin tamamlanmasından ayrıdır.
