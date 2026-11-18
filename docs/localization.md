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

Yadda saxlanmış seçim → brauzerin dəstəklənən dil üstünlüyü → müəyyən ölkə siqnalı → ingilis dili. Ölkə siqnalı ilk girişdə köməkçidir və GPS tələb etmir. Çoxdilli ölkə üçün aydın seçim yoxdursa en istifadə edilir. Yalnız kök giriş dili avtomatik seçir, açıq lokal URL eyni dili saxlayır. Əl ilə dəyişiklik yeni üstünlüyü saxlayır. Kateqoriyada analoji kateqoriyaya, etirafda seçilən dilin lentinə keçilir. Yazı açıqdırsa mətn, mövzu və etirafın dili qorunur.

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
