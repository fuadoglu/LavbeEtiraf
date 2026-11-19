@extends('layouts.app')

@section('title', __('moderation.grant_title'))

@section('content')
    <section class="write-page">
        <h1>{{ __('moderation.grant_title') }}</h1>
        @if(session('status'))<x-status tone="success">{{ session('status') }}</x-status>@endif
        <p>{{ __('moderation.grant_admin_locale') }}</p>
        <form class="write-form" method="post" action="{{ route('moderation.grants.store', ['locale' => request()->route('locale')]) }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
            <x-form-field :label="__('moderation.grant_user')" name="user_id">
                <select name="user_id" required>
                    @foreach($users->where('id', '!=', auth()->id()) as $user)<option value="{{ $user->id }}">{{ $user->email }}</option>@endforeach
                </select>
            </x-form-field>
            <x-form-field :label="__('moderation.grant_role')" name="role"><select name="role" required><option value="moderator">{{ __('moderation.grant_moderator') }}</option><option value="admin">{{ __('moderation.grant_admin') }}</option></select></x-form-field>
            <x-form-field :label="__('moderation.grant_language')" name="locale"><select name="locale" required>@foreach(app(\App\Support\Lavbe\LocaleRegistry::class)->all() as $language)<option value="{{ $language['segment'] }}">{{ $language['native'] }}</option>@endforeach<option value="*">*</option></select></x-form-field>
            <x-form-field :label="__('moderation.grant_reason')" name="reason"><textarea name="reason" rows="3" minlength="5" maxlength="500" required></textarea></x-form-field>
            <button class="button button-primary" type="submit">{{ __('moderation.grant_submit') }}</button>
        </form>
        @forelse($grants as $grant)
            <article class="feed-card">
                <p>{{ $grant->user->email }} · {{ __('moderation.grant_'.$grant->role) }} · {{ $grant->locale }} · {{ $grant->is_active ? __('moderation.grant_active') : __('moderation.grant_inactive') }}</p>
                @if($grant->is_active && $grant->user_id !== auth()->id())
                    <form method="post" action="{{ route('moderation.grants.revoke', ['locale' => request()->route('locale'), 'grantId' => $grant->id]) }}">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                        <x-form-field :label="__('moderation.grant_reason')" name="reason"><textarea name="reason" rows="2" minlength="5" maxlength="500" required></textarea></x-form-field>
                        <button class="button button-secondary" type="submit">{{ __('moderation.grant_revoke') }}</button>
                    </form>
                @endif
            </article>
        @empty
            <x-status>{{ __('moderation.grant_none') }}</x-status>
        @endforelse
        {{ $grants->links('components.pagination') }}
    </section>
@endsection
