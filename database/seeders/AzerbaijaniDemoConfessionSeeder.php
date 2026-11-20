<?php

namespace Database\Seeders;

use App\Models\Confession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Fictional stories for local UI previews, explicitly invoked by the developer. */
class AzerbaijaniDemoConfessionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Demo confessions require the local environment.');
        }

        $stories = [
            'relationships' => [
                'Hələ də iki fincan çıxarıram',
                'Ayrılandan dörd ay keçib. Hamıya deyirəm ki, yaxşıyam, özümə vaxt ayırıram. Dünən səhər çay qoyanda yenə dolabdan iki fincan çıxardım. İkincisini geri qoyanda elə bil kiminsə əlini buraxdım.

Ən çox da böyük şeylər üçün darıxmıram. Axşam marketdən zəng edib “evə nəsə lazımdır?” soruşmağı üçün darıxıram. İndi evdə hər şey var, amma evə gəlmək əvvəlki kimi deyil. Bunu dostlarıma demirəm. Qorxuram yenə “keçəcək” desinlər. Bilirəm, keçəcək. Sadəcə bu gün hələ keçməyib.',
            ],
            'family' => [
                'Atamla danışmağa söz tapa bilmirəm',
                'Atamla telefonda söhbətimiz həmişə eyni olur: “İş necədir?”, “Yaxşı”, “Pulun var?”, “Var”. Sonra anamı çağırır. Elə bil ikimiz də bir-birimizə demək istədiyimiz sözlərin ətrafında dolanırıq.

Keçən həftə evə getmişdim. Gecə su içməyə qalxanda gördüm, köhnə ayaqqabımı yapışdırır. Təzəsini ala bilərəm, özü də bilir. Amma oturub onu düzəldirdi. Yanında bir az dayandım, yenə heç nə deyə bilmədim. Mən atamı çox istəyirəm. Bu cümləni ona demək niyə bu qədər çətindir?',
            ],
            'friends' => [
                'Dostumun sevincinə sevinə bilmədim',
                'Ən yaxın dostum başqa ölkədə iş tapıb. Xəbəri deyəndə onu qucaqladım, güldüm, hətta qeyd etmək üçün yer də seçdim. Evə çatanda isə ağladım. Özümü çox pis dost kimi hiss etdim.

Onun getməyini istəmirəm. Bunu dilə gətirməyə utanıram, çünki bilirəm nə qədər çalışıb. Burada məni izahsız başa düşən bircə o var. Mənə elə gəlir, o gedəndə şəhər də bir az boşalacaq. Biletini birlikdə alacağıq. İndidən özümü hazırlayıram ki, yanında ağlamayım.',
            ],
            'work' => [
                'İşdə hamı məni bacarıqlı bilir',
                'Bu gün iclasda məni nümunə göstərdilər. Hamı gülümsədi, mən də təşəkkür etdim. Heç kim bilmir ki, dünən göndərdiyim hesabatı üç dəfə yoxlamışam, gecə də durub telefondan yenə açmışam.

Hər dəfə təriflənəndə rahatlamaq əvəzinə qorxuram. Düşünürəm, indi məndən daha çox gözləyəcəklər. Bir səhv etsəm, elə bil hamı anlayacaq ki, əslində heç nəyə əmin deyiləm. Evə gələndə kompüteri bağlayıram, amma iş başımda davam edir. Bir axşam özümü sübut etmədən oturub çay içmək istəyirəm.',
            ],
            'education' => [
                'Diplomumu hələ divardan asmamışam',
                'Universiteti bitirəndə anam diplomum üçün çərçivə aldı. İki ildir dolabın içindədir. Oxuduğum sahədə işləmədiyimi soruşanda deyirəm, uyğun yer çıxmayıb. Əslində o sahəni heç vaxt sevməmişəm.

Seçimi özüm etmişdim, bunu da bilirəm. On yeddi yaşımda verdiyim qərara görə indi başqa şey istəməyə haqqım yoxmuş kimi yaşayıram. Axşamlar gizlincə yeni bir sahə öyrənirəm. Gizlincə deyirəm, sanki pis iş görürəm. Ən çox anamın “bəs o qədər əziyyət?” deməyindən qorxuram. O əziyyəti danmıram. Sadəcə qalan ömrümü də ona borclu keçirmək istəmirəm.',
            ],
            'money' => [
                'Hesab gələndə nəfəsimi saxlayıram',
                'Dostlarla görüşə getməzdən əvvəl menyuya internetdən baxıram. Nə yeyəcəyimi yox, nə qədər ödəyəcəyimi hesablayıram. Süfrədə kimsə “gəlin, bərabər bölək” deyəndə gülümsəyirəm. Bəzən o gülüş mənə bir həftəlik yolpuluna başa gəlir.

Heç kimdən incimirəm. Vəziyyətimi bilən yoxdur axı. Sadəcə hər dəfəsində “işim çıxdı” deyib görüşdən yayınmaq da adamı yorur. Çox şey istəmirəm. Bir gün hesabı gözləmədən söhbətə qulaq asmaq istəyirəm.',
            ],
            'identity' => [
                'Hamının yanında bir az başqa adamam',
                'Evdə sakitəm, işdə zarafatcıl, dostların yanında hər şeyə razı. Kiminsə xətrinə dəyməyim deyə elə tez uyğunlaşıram ki, bəzən öz fikrimi sonradan başa düşürəm.

Keçən gün məndən soruşdular ki, ad günümü necə keçirmək istəyirəm. “Siz necə istəsəniz” dedim. Sonra düşündüm, axı bu mənim günümdür. Mən nə istəyirəm? Cavab tapa bilmədim. Otuz yaşım var, özümə bu sualı verməyi indi öyrənirəm.',
            ],
            'values' => [
                'Susduğum üçün özümə əsəbiləşirəm',
                'Qohum məclisində olmayan bir adam haqqında pis danışırdılar. Mən onun tərəfini bilirdim. Bilirdim ki, deyilənlərin yarısı düz deyil. Yenə də səsimi çıxarmadım, boşqabımdakı çörəyi xırdaladım.

Sonra evdə beynimdə yüz dəfə cavab verdim. Guya nə qədər cəsarətli adamam. Üzdə sakitliyi qorudum, amma içimdə heç nə sakit deyil. Özümü yaxşı insan hesab etmək rahatdır. O yaxşılığın bir az narahatlıq yaradacağı yerdə isə susdum. Sabah həmin adama zəng edəcəyəm. Nə deyəcəyimi hələ bilmirəm.',
            ],
            'wellbeing' => [
                'Bu gün pəncərəni açdım',
                'Bir müddətdir ən adi işlər də gözümdə böyüyür. Mesaja cavab vermək, qab yumaq, evdən çıxmaq. Çöldən baxanda yəqin tənbəl görünürəm. Özümə də çox vaxt elə deyirəm, sonra daha da ağırlaşır hər şey.

Bu gün yataq dəstini dəyişdim, pəncərəni açdım. Nə böyük xəbərdir, hə? Amma axşam otağa girəndə ilk dəfə bir az rahat nəfəs aldım. Bunu kiməsə yazmaq istədim, sonra sildim. Burada yazıram. Bu gün bacardığım bu qədər idi və ilk dəfə özümü buna görə danlamaq istəmirəm.',
            ],
            'grief' => [
                'Nənəmin nömrəsini silə bilmirəm',
                'Nənəmin telefon nömrəsi hələ də məndədir. Bəzən adını axtarışda yazıb baxıram. Zəng eləmirəm, qorxuram nömrə başqa birinə verilib, yad adam cavab verər.

O sağ olanda zəngləri qısa kəsirdim. “Sonra danışarıq, nənə, işdəyəm”. Sonra danışmaq üçün o qədər vaxtım var idi guya. İndi bazarda sevdiyi almaları görəndə əlim uzanır, sonra yadıma düşür. Darıxmaq bəzən ağlamaq da deyilmiş. Əlində alma ilə bir neçə saniyə nə edəcəyini bilmədən dayanmaq imiş.',
            ],
            'life-changes' => [
                'Öz evimdə ilk gecə',
                'Bu gün ilk dəfə tək yaşayacağım evə köçdüm. Hamıya şəkil atdım, “azadlıq!” yazdım. Axşam qapı bağlanandan sonra evin səsi dəyişdi. Soyuducunun səsini belə eşidirdim.

Anam zəng edib soruşdu, yemək yemisən? Dedim, hə. Əslində döşəmədə oturub çörəklə pendir yeyirdim, stol hələ gəlməyib. Evə qayıtmaq istədim. Sonra başa düşdüm ki, indi ev bura da olmalıdır. Sevinirəm, qorxuram, darıxıram. Üçünü birdən hiss etmək adamı lap uşaq edir.',
            ],
            'everyday' => [
                'Adımı yadda saxlayıbmış',
                'Hər səhər eyni yerdən çörək alıram. Satıcı ilə salamdan başqa söhbətimiz olmayıb. Dünən pulqabımı evdə unutmuşdum. Növbə arxamda uzandıqca əlimi çantamda boş-boş gəzdirirdim.

Dedi, “apar, sabah verərsən”, bir də adımı çəkdi. Nə vaxt adımı eşitdiyini belə xatırlamıram. Bütün gün o kiçik şey ağlımda qaldı. Son vaxtlar özümü elə görünməz hiss edirdim ki. Sən demə, hər səhər girdiyim balaca bir dükanda kimsə mənim kim olduğumu bilirmiş.',
            ],
        ];

        $additionalStories = [
            'relationships' => [
                'Söhbəti başlamaq üçün doğru anı gözləyirəm',
                'Son vaxtlar münasibətimizdə özümü bir az kənarda hiss edirəm. Bunu deyəndə qarşı tərəfi günahlandırmaqdan qorxuram, ona görə hər dəfə günü adi söhbətlə bitiririk. Keçən bazar birlikdə gəzəndə əlimi tutdu, mən də əvvəlki kimi rahatladım. Deməli, hər şey pis deyil; sadəcə danışmadığımız bir şey var.

İki həftədir fikrimi cümlə-cümlə beynimdə qururam. Hələ deməmişəm. Bəlkə də mükəmməl an yoxdur, sadəcə sakit bir axşam var. Bu gün ilk dəfə “səninlə bir şey danışmaq istəyirəm” deməyə çalışacağam.',
            ],
            'family' => [
                'Bacımın köçəcəyini hamıdan sonra öyrəndim',
                'Bacım başqa şəhərdə iş tapıb. Ailəyə deyəndə hamı sevindi, mən də sevindim. Sonra başa düşdüm ki, bunu məndən bir neçə gün gizlədiblər. Guya xəbər verəndə kədərlənməyim deyə gözləyiblər.

İncidiyimi deməyə utanıram, çünki onun üçün həqiqətən yaxşı fürsətdir. Sadəcə evdə onsuz necə olacağını fikirləşəndə boğazım düyünlənir. Uşaqlıqda eyni otaqda yatırdıq, gecələr pıçıltıyla danışırdıq. İndi ona kömək edib əşyalarını yığacağam. Bir tərəfdən də istəyirəm, kimsə mənə “darıxacaqsan, bilirəm” desin.',
            ],
            'friends' => [
                'Görüşü mən təklif etdim, yenə də özümü artıq hiss etdim',
                'Dostlarla görüşməyi mən təklif etmişdim. Hamı gəldi, söhbət də yaxşı idi. Amma aralarında əvvəlki bir zarafatı danışanda mən heç nə başa düşmədim. Gülümsəyib mövzunu dəyişdim.

Evə qayıdanda düşündüm ki, bəlkə də hamı mənsiz də yaxşı yola gedir. Bu fikirə görə özümə əsəbiləşirəm; dostlarımın xoşbəxt olmasına sevinməliyəm. Sadəcə son vaxtlar söhbətin içində olsam da, kənardan baxırmışam kimi gəlir. Onlardan birinə bunu yazmaq istəyirəm. “Mən də darıxmışam” demək bu qədər çətin olmamalıdır.',
            ],
            'work' => [
                'Əlavə işi yox deyə bilmədim',
                'Bu ay üçüncü dəfə iş yoldaşımın növbəsini mən götürdüm. Hər dəfə “çətinlik deyil” deyirəm, sonra evə çatanda heç kimlə danışmağa halım qalmır. Rəhbərim bunu məsuliyyətli olmağım kimi görür. Mən isə sadəcə kimisə məyus etməkdən çəkinirəm.

Sabah yenə xahiş etsə, yox deməyə çalışacağam. Bu cümləni demək asandır, üzbəüz olanda isə dilim dolaşır. İşimi sevirəm, amma hər boşluğu mən doldurmalı deyiləm, bunu özümə izah etməyə çalışıram. Bəlkə bu dəfə “bu axşam alınmayacaq” deyib evə vaxtında gedərəm.',
            ],
            'education' => [
                'İxtisasımı dəyişmək istədiyimi evdəkilərə deyə bilmirəm',
                'İkinci kursdan bəri başqa sahəyə marağım var. Dərslərimi buraxmıram, imtahanları verirəm, hamı da elə bilir ki, hər şey qaydasındadır. Evdə bu barədə danışmağa başlayanda “bir az da döz” deyirlər. Mən də susuram.

Bu gün başqa ixtisasın dərsinə ilk dəfə qonaq kimi girdim. Arxa sırada oturdum, heç kim məni tanımırdı. Müəllim mövzunu izah edəndə qeydlər götürməyə başladım və uzun vaxtdan sonra dərsin bitməsini istəmədim. Hələ qərar verməmişəm. Sadəcə sevdiyim bir şeyi demək üçün əvvəlcə özümə icazə verdim.',
            ],
            'money' => [
                'Dostlarla hesabı bərabər bölmək mənə ağır gəlir',
                'Dostlarım bilmir ki, ayın axırına yaxın hər xərci iki dəfə düşünürəm. Kafedə hesab gələndə “hamımız bərabər ödəyək” deyirlər, mən də başımla təsdiqləyirəm. Bəzən sifariş etmədiyim şeylərin pulunu da verirəm, sonra evə avtobusla yox, piyada qayıdıram.

Onlara demək istəyirəm, amma elə bilirəm, xəsis görünəcəyəm. Əslində heç kimə borclu qalmaq istəmirəm, sadəcə imkanım olmayanı da ödəməyə çalışıram. Gələn dəfə əvvəlcədən “hər kəs öz sifarişini ödəsə, mənə rahat olar” deyəcəyəm. Bunu yazanda sadə görünür. Görək, ucadan deyə biləcəyəmmi.',
            ],
            'identity' => [
                'İlk dəfə ürəyim istəmədiyi yerə getmədim',
                'Dostum məni bir tədbirə çağırdı. Getmək istəmirdim, amma həmişəki kimi bəhanə tapmaq əvəzinə “bu dəfə evdə qalmaq istəyirəm” yazdım. Mesajı göndərənə qədər bir neçə dəfə sildim.

Heç kim incimədi. Hətta “dincəl, sonra görüşərik” cavabı gəldi. Bunu bilmək həm sevindirdi, həm də bir az utandırdı. Deməli, bu qədər vaxt özüm-özümə çətinlik yaratmışam. Hər şeyə hə deyəndə mehriban olduğumu düşünürdüm. İndi yavaş-yavaş anlayıram ki, istəmədiyimə yox demək də münasibəti pozmur. Bunu öyrənmək üçün xeyli gecikmişəm.',
            ],
            'values' => [
                'Fikrimi dəyişdiyimi etiraf etmək istəyirəm',
                'Bir mövzuda illərdir eyni fikirdə olduğumu deyirdim. Əslində son vaxtlar oxuduqlarım və yaşadıqlarım məni dəyişib. Bunu yaxınlarıma deməkdən çəkinirəm, çünki qərarsız görünəcəyimdən qorxuram.

Amma əvvəl bilmədiyim şeyi indi başqa cür görürəmsə, bunu gizlətməyin nə mənası var? Dünən söhbətdə “əvvəl belə düşünürdüm, indi əmin deyiləm” dedim. Heç kim məni qınamadı. Bəlkə də mən özümə qarşı başqalarından daha sərtəm. İnsan bəzən yeni bir şey öyrənəndə köhnə fikrini sakitcə yerə qoya bilərmiş.',
            ],
            'wellbeing' => [
                'Dincələndə də özümü günahkar hiss edirəm',
                'Bu gün işlərimi bitirmişdim, amma divanda oturanda dərhal ağlıma sabahın siyahısı gəldi. Bir saat dincəlməyi özümə çox gördüm. Sonra fikirləşdim ki, evdə hər kəs yorulanda dincəlir, mən niyə bunu yalnız hər şey qurtarandan sonraya saxlayıram?

Telefonu kənara qoyub çay içdim. Heç nə baş vermədi, dünya dağılmadı. Yenə də içimdə balaca bir narahatlıq qaldı. Bəlkə dincəlmək də öyrənməli olduğum bir şeydir. Bu axşam özümə “indi heç nə etməmək olar” deməyə çalışacağam.',
            ],
            'grief' => [
                'Atamın səs yazısını təsadüfən tapdım',
                'Telefonumda köhnə bir səs yazısı çıxdı. Atam məndən bazardan nə almaq lazım olduğunu soruşur, mən də tələsik cavab verirəm. Səs yazısı bir neçə saniyədir. İllərdir orada qaldığını bilmirdim.

Əvvəlcə dinləməyə qorxdum. Sonra dəfələrlə açdım, sanki həmin adi günün içində bir az da qala bilərəm. Onun səsi elə tanışdır ki, bir anlıq başqa otaqdan çağıracaq kimi oldum. Darıxmaq bəzən böyük xatirələr deyilmiş. Telefonun içində qalan, vaxtilə heç əhəmiyyət vermədiyin qısa bir cümlə imiş.',
            ],
            'life-changes' => [
                'Yeni şəhərdə ilk həftəm tamam oldu',
                'İş üçün başqa şəhərə köçməyimə bir həftə oldu. Küçələrin adını hələ yadda saxlaya bilmirəm. Hər gün evdən bir az tez çıxıram ki, səhv avtobusa minməyə vaxt qalsın.

Burada hər şey təzədir, amma axşamlar köhnə evimizin mətbəxi yadıma düşür. Anamın qazan qapaqlarını səs-küylə bağlamağı, qonşunun eyvandan salam verməyi. Dünən bazardan özümə bir dibçək gül aldım, pəncərənin qabağına qoydum. Hələ ev kimi hiss olunmur, amma bəlkə belə-belə olar. Bu həftə ilk dəfə yolu soruşmadan işə gedə bildim; balaca şeydir, yenə də sevindim.',
            ],
            'everyday' => [
                'Qonşum üçün qapını saxladım, o da mənə gülümsədi',
                'Binanın girişində əlləri dolu bir qonşuya rast gəldim. Qapını açıq saxladım, o da “çox sağ ol” deyib gülümsədi. Cəmi bir neçə saniyə çəkdi.

Son vaxtlar günlər bir-birinə oxşayır. Səhər tələs, işə get, evə qayıt. Axşam o kiçik an yadıma düşəndə başa düşdüm ki, günün içində yaxşı bir şey də olub. Böyük dəyişiklik deyil, heç kimin xəbəri də yoxdur. Amma bəzən günü yüngülləşdirmək üçün elə bu qədər bəs edir.',
            ],
        ];

        $storyBatches = [1 => $stories, 2 => $additionalStories];

        DB::transaction(function () use ($storyBatches): void {
            foreach ($storyBatches as $version => $stories) {
                foreach ($stories as $category => [$title, $body]) {
                    $key = hash('sha256', 'lavbe-local-az-demo-v'.$version.'|'.$category);
                    if (Confession::query()->where('submission_key_hash', $key)->exists()) {
                        continue;
                    }
                    $storedCategory = $version < 3 && $category === 'grief' ? 'life-changes' : $category;

                    $confession = Confession::query()->create([
                        'locale' => 'az',
                        'category_key' => $storedCategory,
                        'status' => 'unpublished',
                        'title' => $title,
                        'body' => $body,
                        'excerpt' => Str::limit($body, 220),
                        'public_id' => (string) Str::uuid(),
                        'author_alias' => 'Lavbe #'.strtoupper(bin2hex(random_bytes(4))),
                        'submission_key_hash' => $key,
                        'reviews_open' => true,
                    ]);
                    $revision = $confession->revisions()->create([
                        'revision' => 1,
                        'title' => $title,
                        'body' => $body,
                        'category_key' => $storedCategory,
                        'status' => 'approved',
                    ]);
                    $confession->forceFill([
                        'status' => 'public',
                        'public_revision_id' => $revision->id,
                        'published_at' => now(),
                        'public_updated_at' => now(),
                        'ever_public' => true,
                    ])->save();
                }
            }
        });

        $parenthoodStories = [
            [
                'Bu gün oğluma atamın mənə dediyi o cümləni dedim',
                'Bu gün oğlum nəyisə yerə salanda əsəbiləşib atamın illər əvvəl mənə dediyi cümləni təkrar etdim. Söz ağzımdan çıxan kimi otaq sakitləşdi. Oğlum başını aşağı saldı, mən isə öz uşaqlığıma qayıtmış kimi oldum.

İllərdir özümə söz vermişdim ki, onunla başqa cür danışacağam. Sonra üzr istədim, yanında oturub nə üçün əsəbiləşdiyimi izah etdim. Hələ də içimdə narahatlıq var. Amma bu dəfə susub keçmədim. Bəlkə də dəyişmək heç vaxt səhv etməmək deyil, səhvi görəndə geri dönüb uşağın gözünə baxmaqdır.',
            ],
            [
                'Valideyn olmaqdan qorxduğumu heç kimə deyə bilmirəm',
                'Hamı uşaq sahibi olmağın nə qədər gözəl olduğunu danışır. Mən də uşaqları sevirəm, amma bəzən bu qərarın məsuliyyətini düşünəndə içimə qorxu düşür. Özümə sual verirəm: yorulanda səbirli ola biləcəyəmmi, ona ehtiyacı olan diqqəti verəcəyəmmi?

Bu tərəddüdü dilə gətirəndə elə bil insanları məyus edəcəyəm. Ona görə mövzunu dəyişirəm. Bəlkə cavabı indi bilməmək də dürüst bir cavabdır. Hələ qərar verməmişəm, sadəcə öz səsimi başqalarının gözləntisindən ayırmağa çalışıram.',
            ],
        ];

        DB::transaction(function () use ($parenthoodStories): void {
            foreach ($parenthoodStories as $index => [$title, $body]) {
                $key = hash('sha256', 'lavbe-local-az-demo-v3|parenthood|'.($index + 1));
                if (Confession::query()->where('submission_key_hash', $key)->exists()) {
                    continue;
                }

                $confession = Confession::query()->create([
                    'locale' => 'az',
                    'category_key' => 'grief',
                    'status' => 'unpublished',
                    'title' => $title,
                    'body' => $body,
                    'excerpt' => Str::limit($body, 220),
                    'public_id' => (string) Str::uuid(),
                    'author_alias' => 'Lavbe #'.strtoupper(bin2hex(random_bytes(4))),
                    'submission_key_hash' => $key,
                    'reviews_open' => true,
                ]);
                $revision = $confession->revisions()->create([
                    'revision' => 1,
                    'title' => $title,
                    'body' => $body,
                    'category_key' => 'grief',
                    'status' => 'approved',
                ]);
                $confession->forceFill([
                    'status' => 'public',
                    'public_revision_id' => $revision->id,
                    'published_at' => now(),
                    'public_updated_at' => now(),
                    'ever_public' => true,
                ])->save();
            }
        });
    }
}
