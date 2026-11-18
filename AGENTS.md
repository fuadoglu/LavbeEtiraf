# LavbeEtiraf · Codex iş qaydası

## Başlamazdan əvvəl

1. Cari branch-i və iş ağacını yoxla. Mövcud dəyişiklikləri qoruyaraq işlə.
2. [README.md](README.md), [məhsul qərarları](docs/product.md), [backlog](docs/delivery/backlog.md) və [qəbul matrisini](docs/delivery/acceptance.md) oxu. Sonra tapşırığa aid əsas sənədi oxu. İlk tətbiq işi LAV-001-dir.
3. Bir issue və ya aydın iş həcmi üzrə irəlilə. Qəbul meyarlarını koddan əvvəl müəyyən et. Sənədlərdə ziddiyyət tapdıqda qərarın əsas sənədini və əlaqəli test meyarını eyni PR-da uyğunlaşdır. Hüquqi, təhlükəsizlik və ictimai yayım səlahiyyətləri barədə faktı təxmin etmə.

## Dəyişməz tələblər

- PHP 8.4/8.5, Laravel 13, Blade və MySQL/MariaDB. İstehsalda Node prosesi, Redis və ya daimi worker tələb etmə. Səhifələr ilkin HTML-də oxuna bilsin.
- Qonaq etiraf və rəy göndərə bilər. Etiraf, rəy və müəllifin davamı təsdiqsiz açıq görünmür. Sahibliyi hər dəyişiklikdə serverdə yoxla. Açıq URL, anonim ad və bərpa sirri fərqli dəyərlərdir.
- Təsdiqlənmiş açıq etiraf öz dilində sabit URL, daxili link və sitemap qeydi alır. Reklam uyğunluğu ayrıdır. Şəxsi ekran və PWA keşi həssas məlumatı ifşa etməməlidir.
- 17 dilin bütün mətnlərini Codex yazır, məhsul sahibi yekun dil baxışını keçirir. 12 kateqoriyanın sabit açarlarını qoru. Tərcümə düzəlişi üçün verilənlər bazasındakı etirafları dəyişmə.
- Real etiraflar, şikayətlər, e-poçtlar, bərpa açarları, sessiyalar və istehsal identifikatorlarının məxfi hissələri kod, fixture, log, issue, PR və ekran görüntülərinə düşməsin.
- Scope daxilində mövcud komponentləri və qaydaları təkrar istifadə et. İctimai buraxılış, reklam aktivləşdirməsi və xidmət hesablarında əməliyyat yalnız səlahiyyət və faktiki konfiqurasiya ilə icra olunur.

## GitHub və yoxlama

- İlk repo bazası xaric, `main`-dən `codex/lav-<n>-<mövzu>` branch-i aç. Məhdud həcmli commit et, branch-i push et və issue-ya bağlı PR aç. Mövcud tarixçəni qoruyaraq işlət. Merge sahibin və ya ayrıca səlahiyyətli qaydanın qərarıdır.
- Hər PR məqsədi, dəyişən istifadəçi davranışını, qəbul ID-lərini, işlədilmiş əmrləri, nəticələri və qalan yoxlamaları göstərir. Dəyişiklik hazır deyilsə draft PR istifadə et. İcra edilməyən yoxlama keçmiş kimi qeyd olunmur.
- Hazırkı sənəd əmri `python3 scripts/check_docs.py`-dir. LAV-001 tətbiqin faktiki quraşdırma, lint, test və CI əmrlərini əlavə edir. Sonrakı işlərdə hər dəyişiklik üçün aidiyyəti avtomatik və ya cihaz sınağını işlə.
- İş o zaman tamamlanır ki, qəbul meyarı sübutla bağlanır, sənədlər yenilənir, məxfilik və açıq HTML davranışı qorunur, branch push olunur və PR-da müqayisə edilə bilən nəticə verilir. Sprint nəticəsi daxili baxışdır. İctimai buraxılışı [qapılar](docs/delivery/operations.md) müəyyən edir.
