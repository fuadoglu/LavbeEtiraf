# Buraxılış backlog-u

Məhsul sahibi istiqaməti və dil baxışını təsdiqləyir. Codex iş həcmini ölçür, icra edir, nəticəni sınaqdan keçirir, branch-i push edir və PR açır. GitHub Issues iş statusunun mənbəyidir. Bu fayl asılılıq sırasını saxlayır. Sprintin nəticəsi işlək daxili artımdır. Açıq buraxılış [buraxılış qapıları](operations.md) ilə idarə olunur.

| Sprint | Məqsəd | İşlər | Qəbul |
|---|---|---|---|
| 1 | Serverdən lokal lent və UI baza | LAV-001 tətbiq/CI, LAV-002 Blade və mövzu komponentləri, LAV-003 reyestr və ortaq lent | BAS-01, UI-01, UI-02, LOC-01 |
| 2 | Qonaq etirafı yayıma qədər | LAV-004 yazı/sahiblik, LAV-005 moderasiya və vəziyyət, LAV-006 lokal oxu URL-si və sitemap | OWN-01, MOD-01, SEO-01, SEO-02 |
| 3 | Rəy, şikayət, davam və şəxsi idarəetmə | LAV-007 rəylər, LAV-008 şikayət/etiraz, LAV-009 hesab və bildiriş | MOD-02, MOD-03, OWN-02 |
| 4 | 17 dil və PWA tamlığı | LAV-010 bütün dil mətnləri və müqayisə, LAV-011 PWA/oflayn, LAV-012 cihaz sınaqları | LOC-02, PWA-01, UI-03 |
| 5 | Ölçüm və sabit buraxılış | LAV-013 GA4/CMP/AdSense, LAV-014 IndexNow/xarici doğrulama, LAV-015 hosting/bərpa/yük | ADV-01, ADV-02, SEO-03, OPS-01, OPS-02 |

## LAV-001 · Tətbiq əsası

- Laravel 13, azı PHP 8.4, Blade, nümunə mühit faylı və MySQL ailəsində ilkin smoke test. İstehsal entry point açıq qovluqdadır.
- Composer lock, kiçik tətbiq health yoxlaması, lokal və CI əmrləri. Birinci CI tətbiq testini, sənəd yoxlamasını və lazım olan konfiqurasiya yoxlamalarını icra edə bilir.
- Könüllü asset build hazırlığı hostda Node prosesinə ehtiyac yaratmır. İlkin skeleton sintetik nümunələrlə işləyir.
- Məhsul funksiyaları aidiyyəti işlərdə qoşulur. İlk PR BAS-01 meyarını, faktiki işlədilmiş əmrləri və shared hosting fərziyyələrini göstərir.

## İcra dövrü

Sprint əvvəlində məqsəd, seçilmiş işlər, asılılıqlar və test üsulu qeyd olunur. Bir issue → branch → commit → push → draft və ya hazır PR → CI və sahib baxışı. PR-da issue ilə əlaqə, dəyişən davranış və qəbul ID-si olur. İlk baza commitindən sonra main üçün PR və mövcud sabit CI yoxlamalarının məcburi edilməsi repo sahibinin əməliyyat addımıdır. CI adı sabitləşəndən sonra qayda tətbiq edilir. Sprint sonunda işlək axın göstərilir, bloklar və növbəti prioritet qeyd olunur. Holdu faktiki məsul və səbəblə saxla. Sənədlər push olunanda yeni Codex icrası avtomatik başlamır.
