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
                <select @if($errors->has('user_id')) aria-describedby="user_id-description" @endif aria-invalid="{{ $errors->has('user_id') ? 'true' : 'false' }}" id="user_id" name="user_id" required>
                    @foreach($users->where('id', '!=', auth()->id()) as $user)<option value="{{ $user->id }}">{{ $user->email }}</option>@endforeach
                </select>
            </x-form-field>
            <x-form-field :label="__('moderation.grant_role')" name="role"><select @if($errors->has('role')) aria-describedby="role-description" @endif aria-invalid="{{ $errors->has('role') ? 'true' : 'false' }}" id="role" name="role" required><option value="moderator">{{ __('moderation.grant_moderator') }}</option><option value="admin">{{ __('moderation.grant_admin') }}</option></select></x-form-field>
            <x-form-field :label="__('moderation.grant_language')" name="locale"><select @if($errors->has('locale')) aria-describedby="locale-description" @endif aria-invalid="{{ $errors->has('locale') ? 'true' : 'false' }}" id="locale" name="locale" required>@foreach(app(\App\Support\Lavbe\LocaleRegistry::class)->all() as $language)<option value="{{ $language['segment'] }}">{{ $language['segment'] }}</option>@endforeach<option value="*">*</option></select></x-form-field>
            <x-form-field :label="__('moderation.grant_reason')" name="reason"><textarea @if($errors->has('reason')) aria-describedby="reason-description" @endif aria-invalid="{{ $errors->has('reason') ? 'true' : 'false' }}" id="reason" name="reason" rows="3" minlength="{{ config('lavbe.limits.moderation_reason_min') }}" maxlength="{{ config('lavbe.limits.moderation_reason') }}" required></textarea></x-form-field>
            <button class="button button-primary" type="submit">{{ __('moderation.grant_submit') }}</button>
        </form>
        @forelse($grants as $grant)
            <article class="feed-card">
                <p>{{ $grant->user->email }} · {{ $grant->role }} · {{ $grant->locale }} · {{ $grant->is_active ? __('moderation.grant_active') : __('moderation.grant_inactive') }}</p>
                @if($grant->is_active && $grant->user_id !== auth()->id())
                    <form method="post" action="{{ route('moderation.grants.revoke', ['locale' => request()->route('locale'), 'grantId' => $grant->id]) }}">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ bin2hex(random_bytes(16)) }}">
                        <x-form-field :label="__('moderation.grant_reason')" name="reason" :id="'revoke-reason-'.$grant->id"><textarea @if($errors->has('reason')) aria-describedby="revoke-reason-{{ $grant->id }}-description" @endif aria-invalid="{{ $errors->has('reason') ? 'true' : 'false' }}" id="revoke-reason-{{ $grant->id }}" name="reason" rows="2" minlength="{{ config('lavbe.limits.moderation_reason_min') }}" maxlength="{{ config('lavbe.limits.moderation_reason') }}" required></textarea></x-form-field>
                        <button class="button button-secondary" type="submit">{{ __('moderation.grant_revoke') }}</button>
                    </form>
                @endif
            </article>
        @empty
            <x-status>{{ __('moderation.grant_none') }}</x-status>
        @endforelse
        {{ $grants->links() }}
    </section>
@endsection
