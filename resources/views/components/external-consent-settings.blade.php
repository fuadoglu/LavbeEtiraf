<section class="reader-consent-settings" aria-labelledby="external-consent-title">
    <h2 id="external-consent-title">{{ __('consent.external_title') }}</h2>
    <p>{{ __('consent.external_body') }}</p>
    @foreach(['analytics' => \App\Support\Analytics\ExternalTags::ANALYTICS_COOKIE, 'ads' => \App\Support\Analytics\ExternalTags::ADS_COOKIE] as $purpose => $cookieName)
        <form method="post" action="{{ route('external-consent.update', ['locale' => request()->route('locale')]) }}" class="reader-consent-actions">
            @csrf
            <input type="hidden" name="purpose" value="{{ $purpose }}">
            <p>{{ __('consent.external_'.$purpose) }}: {{ request()->cookie($cookieName) === 'accepted' ? __('consent.status_accepted') : __('consent.status_declined') }}</p>
            <button class="button button-secondary" type="submit" name="choice" value="decline">{{ __('consent.decline') }}</button>
            <button class="button button-primary" type="submit" name="choice" value="accept">{{ __('consent.allow') }}</button>
        </form>
    @endforeach
</section>
