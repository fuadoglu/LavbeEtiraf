@php
    $readerConsent = request()->cookie(config('analytics.reader_consent_cookie_name'));
@endphp
<section class="reader-consent-settings" aria-labelledby="reader-consent-settings-title">
    <h2 id="reader-consent-settings-title">{{ __('consent.settings_title') }}</h2>
    <p>{{ __('consent.settings_body') }}</p>
    @if(! config('analytics.unique_read_tracking_enabled'))
        <p role="status">{{ __('consent.tracking_disabled') }}</p>
    @elseif($readerConsent === 'accepted')
        <p role="status">{{ __('consent.status_accepted') }}</p>
    @elseif($readerConsent === 'declined')
        <p role="status">{{ __('consent.status_declined') }}</p>
    @endif
    <form method="post" action="{{ route('reader-consent.update', ['locale' => request()->route('locale', 'en')]) }}" class="reader-consent-actions">
        @csrf
        <input type="hidden" name="return_to" value="{{ request()->getPathInfo() }}">
        @if(config('analytics.unique_read_tracking_enabled'))
            <button class="button button-primary" type="submit" name="choice" value="accept">{{ __('consent.change_allow') }}</button>
        @endif
        <button class="button button-secondary" type="submit" name="choice" value="decline">{{ __('consent.change_decline') }}</button>
    </form>
</section>
