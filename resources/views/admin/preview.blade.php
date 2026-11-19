@extends('layouts.app')

@section('title', 'Moderasiya görünüşü · Lavbe')

@section('content')
    <section class="stack" aria-labelledby="admin-title">
        <div class="page-heading">
            <h1 id="admin-title">Moderasiya</h1>
            <p>İnkişaf görünüşü · yalnız sintetik nümunə</p>
        </div>
        <x-status tone="warning" title="Bu, admin paneli deyil">
            Moderator girişi və real moderasiya əməliyyatları sonrakı issue-larda qurulur. Bu səhifə lokal və test mühitində UI baxışı üçündür.
        </x-status>
        <nav class="admin-tabs" aria-label="Moderasiya bölmələri">
            <a href="#confessions" aria-current="page">Etiraflar</a>
            <a href="#comments">Rəylər</a>
            <a href="#reports">Şikayətlər</a>
        </nav>
        <article class="story-card stack" id="confessions">
            <div class="hstack muted"><x-icon name="shield-check" /> <span>Sintetik etiraf · az · baxış gözləyir</span></div>
            <h2 class="story-title">Nümunə material, real istifadəçi məlumatı deyil</h2>
            <p class="story-excerpt">Bu kart komponentin ölçüsünü və məlumat hiyerarxiyasını göstərir.</p>
            <div class="hstack">
                <button class="button" type="button" disabled>Təsdiqlə</button>
                <button class="button button-secondary" type="button" disabled>Redaktə et</button>
                <button class="button button-secondary" type="button" data-dialog-open="preview-note">Baxış qeydi</button>
            </div>
        </article>
        <x-feed-card topic="Ailə" title="Sintetik etiraf kartı" excerpt="Təsdiqlənmiş məzmun kartda bu iyerarxiya ilə görünəcək." :continued="true" />
        <details class="component-sample">
            <summary>Yüklənmə skeleton-u</summary>
            <x-feed-skeleton :count="2" />
        </details>
        <x-form-field label="Material axtarışı" name="preview-search" hint="Nümunə sahədir; axtarış funksiyası qoşulmayıb.">
            <input type="search" placeholder="Etiraf üzrə axtar" disabled>
        </x-form-field>
    </section>
@endsection

@push('dialogs')
    <x-dialog id="preview-note" title="İnkişaf görünüşü">
        Bu pəncərə lokal UI baxışı üçündür. Moderator səlahiyyəti və materiala çıxış hələ tətbiq edilməyib.
    </x-dialog>
@endpush
