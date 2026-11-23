@extends('layouts.app')

@section('title', __('moderation.grant_title'))

@section('content')
    <x-moderation-shell :title="__('moderation.grant_title')">
        <div class="moderation-grants-grid">
            <section class="moderation-panel" aria-labelledby="grant-add-title">
                <header class="moderation-panel-heading">
                    <h2 id="grant-add-title">{{ __('moderation.grant_submit') }}</h2>
                    <p>{{ __('moderation.grant_admin_locale') }}</p>
                </header>
                <form method="post" action="{{ route('moderation.grants.search', ['locale' => request()->route('locale')]) }}" class="moderation-search">
                    @csrf
                    <label for="user_search">{{ __('moderation.grant_user') }}</label>
                    <div class="moderation-search-row">
                        <input id="user_search" type="search" name="user_search" value="{{ $userSearch }}" maxlength="{{ config('lavbe.limits.account_email') }}" required>
                        <button class="button button-secondary" type="submit"><x-icon name="search" class="icon-small" />{{ __('lavbe.search_submit') }}</button>
                    </div>
                </form>
                <form class="moderation-decision-form" method="post" action="{{ route('moderation.grants.store', ['locale' => request()->route('locale')]) }}">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                    <x-form-field :label="__('moderation.grant_user')" name="user_id">
                        <select @if($errors->has('user_id')) aria-describedby="user_id-description" @endif aria-invalid="{{ $errors->has('user_id') ? 'true' : 'false' }}" id="user_id" name="user_id" required>
                            <option value="">{{ __('moderation.grant_user') }}</option>
                            @foreach($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->email }}</option>@endforeach
                        </select>
                    </x-form-field>
                    <div class="moderation-form-row">
                        <x-form-field :label="__('moderation.grant_role')" name="role"><select id="role" name="role" @if($errors->has('role')) aria-describedby="role-description" @endif aria-invalid="{{ $errors->has('role') ? 'true' : 'false' }}" required><option value="moderator" @selected(old('role') === 'moderator')>{{ __('moderation.grant_moderator') }}</option><option value="admin" @selected(old('role') === 'admin')>{{ __('moderation.grant_admin') }}</option></select></x-form-field>
                        <x-form-field :label="__('moderation.grant_language')" name="locale"><select id="locale" name="locale" @if($errors->has('locale')) aria-describedby="locale-description" @endif aria-invalid="{{ $errors->has('locale') ? 'true' : 'false' }}" required>@foreach(app(\App\Support\Lavbe\LocaleRegistry::class)->all() as $language)<option value="{{ $language['segment'] }}" @selected(old('locale') === $language['segment'])>{{ $language['native'] }}</option>@endforeach<option value="*" @selected(old('locale') === '*')>*</option></select></x-form-field>
                    </div>
                    <x-form-field :label="__('moderation.grant_reason')" name="reason"><textarea id="reason" name="reason" rows="3" @if($errors->has('reason')) aria-describedby="reason-description" @endif aria-invalid="{{ $errors->has('reason') ? 'true' : 'false' }}" minlength="{{ config('lavbe.limits.moderation_reason_min') }}" maxlength="{{ config('lavbe.limits.moderation_reason') }}" required>{{ old('reason') }}</textarea></x-form-field>
                    <button class="button button-primary" type="submit" @disabled($users->isEmpty())>{{ __('moderation.grant_submit') }}</button>
                </form>
                <x-pagination :paginator="$users" method="post" :action="route('moderation.grants.search', ['locale' => request()->route('locale')])" :fields="['user_search' => $userSearch, 'grants_page' => $grants->currentPage()]" />
            </section>
            <section class="moderation-panel" aria-labelledby="grant-list-title">
                <header class="moderation-panel-heading"><h2 id="grant-list-title">{{ __('moderation.grant_title') }}</h2></header>
                <div class="moderation-grant-list">
                    @forelse($grants as $grant)
                        <article class="moderation-grant">
                            <div class="moderation-card-meta">
                                <span class="moderation-pill">{{ $grant->role === 'admin' ? __('moderation.grant_admin') : __('moderation.grant_moderator') }}</span>
                                <span>{{ $grant->locale === '*' ? __('moderation.all_languages') : strtoupper($grant->locale) }}</span>
                                <span class="moderation-pill @if(! $grant->is_active) moderation-pill--inactive @endif">{{ $grant->is_active ? __('moderation.grant_active') : __('moderation.grant_inactive') }}</span>
                            </div>
                            <h3>{{ $grant->user->email }}</h3>
                            @if($grant->is_active && $grant->user_id !== auth()->id())
                                <form class="moderation-revoke-form" method="post" action="{{ route('moderation.grants.revoke', ['locale' => request()->route('locale'), 'grantId' => $grant->id]) }}">
                                    @csrf
                                    <input type="hidden" name="idempotency_key" value="{{ \App\Support\Security\IdempotencyKey::generate() }}">
                                    <x-form-field :label="__('moderation.grant_reason')" name="reason" :id="'revoke-reason-'.$grant->id"><textarea id="revoke-reason-{{ $grant->id }}" name="reason" rows="2" @if($errors->has('reason')) aria-describedby="revoke-reason-{{ $grant->id }}-description" @endif aria-invalid="{{ $errors->has('reason') ? 'true' : 'false' }}" minlength="{{ config('lavbe.limits.moderation_reason_min') }}" maxlength="{{ config('lavbe.limits.moderation_reason') }}" required></textarea></x-form-field>
                                    <button class="button button-secondary" type="submit">{{ __('moderation.grant_revoke') }}</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <div class="moderation-empty"><x-icon name="users-round" /><p>{{ __('moderation.grant_none') }}</p></div>
                    @endforelse
                </div>
                <x-pagination :paginator="$grants" method="post" :action="route('moderation.grants.search', ['locale' => request()->route('locale')])" :fields="['user_search' => $userSearch, 'users_page' => $users->currentPage()]" />
            </section>
        </div>
    </x-moderation-shell>
@endsection
