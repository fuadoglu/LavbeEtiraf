# Lavbe.com · Etiraflar Vadisi

Lavbe telefonla anonim etiraf yazmaq, həmin dildə başqa etirafları oxumaq və bir hekayənin davamına qayıtmaq üçün mətn platformasıdır. Hədəf bir dəfəlik tam ictimai buraxılışdır. Sprintlər daxili, yoxlanıla bilən nəticələr verir.

| Mövzu | Əsas sənəd |
|---|---|
| Codex-in iş qaydası | [AGENTS.md](AGENTS.md) |
| Məhsul, rollar və istifadəçi axınları | [Məhsul](docs/product.md) |
| Görünüş və ekran vəziyyətləri | [Dizayn sistemi](docs/ui/design-system.md) · [Ekran və lent](docs/ui/screens-and-states.md) · [Mobil və masaüstü atlas](docs/ui/wireframes.html) |
| 17 dil və 12 kateqoriya | [Lokalizasiya](docs/localization.md) |
| Məlumat, shared hosting və PWA | [Arxitektura](docs/architecture/system.md) · [Sahiblik](docs/architecture/ownership-and-publication.md) · [Lent müqaviləsi](docs/architecture/feed-contract.md) |
| Moderasiya, hüquqi və məlumat qaydaları | [Moderasiya və məxfilik](docs/moderation-privacy.md) |
| Axtarış, reklam və ölçüm | [SEO, AdSense və analitika](docs/seo-ads-analytics.md) · [Trafik və məxfilik matrisi](docs/architecture/traffic-and-privacy.md) |
| Sprint, test və buraxılış | [Backlog](docs/delivery/backlog.md) · [Qəbul matrisi](docs/delivery/acceptance.md) · [İstismar](docs/delivery/operations.md) |

## İlk icra işi

[Backlog-dakı LAV-001](docs/delivery/backlog.md#lav-001--tətbiq-əsası) tətbiq əsasını və real CI yoxlamalarını qurur. Bu başlanğıc paketində tətbiq kodu hələ yoxdur. Hər növbəti PR sənədin istinad etdiyi qəbul meyarını və faktiki test nəticəsini göstərir.

Sənədləri `python3 scripts/check_docs.py`, validator sınaqlarını `PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s scripts` ilə yoxla. Ekran atlasını `node scripts/check_wireframes.mjs` ilə məntiq səviyyəsində yoxla, vizual baxış üçün HTML faylını lokal brauzerdə aç. Node yalnız development yoxlaması üçündür. GitHub-da issue və PR-larda yalnız sintetik nümunələrdən istifadə edilir. Canlı hosting, domen və reklam hesabı aktivləşdirmələri [buraxılış meyarları](docs/delivery/operations.md) ilə idarə olunur.
