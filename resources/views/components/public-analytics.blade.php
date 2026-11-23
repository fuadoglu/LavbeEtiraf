@php($measurementId = app(\App\Support\Analytics\ExternalTags::class)->analytics(request()))
@if($measurementId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($measurementId) }}" referrerpolicy="no-referrer"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', {{ \Illuminate\Support\Js::from($measurementId) }}, {send_page_view: false, allow_google_signals: false, allow_ad_personalization_signals: false});
        gtag('event', 'page_view', {
            content_locale: {{ \Illuminate\Support\Js::from(app()->getLocale()) }},
            page_type: {{ \Illuminate\Support\Js::from($pageType) }},
            page_location: {{ \Illuminate\Support\Js::from(rtrim(config('app.url'), '/').'/'.request()->route('locale').'/'.($pageType === 'confession' ? 'confession' : ($pageType === 'topic' ? 'topics' : '')) ) }},
            page_title: {{ \Illuminate\Support\Js::from(config('app.name')) }},
            page_referrer: ''
        });
    </script>
@endif
