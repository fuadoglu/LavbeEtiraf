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

        DB::transaction(function () use ($stories): void {
            foreach ($stories as $category => [$title, $body]) {
                $key = hash('sha256', 'lavbe-local-az-demo-v1|'.$category);
                if (Confession::query()->where('submission_key_hash', $key)->exists()) {
                    continue;
                }

                $confession = Confession::query()->create([
                    'locale' => 'az',
                    'category_key' => $category,
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
                    'category_key' => $category,
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
