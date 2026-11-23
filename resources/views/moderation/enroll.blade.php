@extends('layouts.app')

@section('title', __('moderation.setup_title'))

@section('content')
    <section class="write-page moderation-auth">
        <h1>{{ __('moderation.setup_title') }}</h1>
        <p>{{ __('moderation.setup_body') }}</p>
        <p><strong>{{ __('moderation.secret') }}</strong></p>
        <p><code dir="ltr">{{ $secret }}</code></p>
        <p><strong>{{ __('moderation.provisioning_uri') }}</strong></p>
        <p><code dir="ltr">{{ $provisioningUri }}</code></p>
        <form method="post" action="{{ route('moderation.mfa.confirm', ['locale' => request()->route('locale')]) }}">
            @csrf
            <x-form-field :label="__('moderation.verify_code')" name="code"><input @if($errors->has('code')) aria-describedby="code-description" @endif aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" required></x-form-field>
            @error('code')<p role="alert">{{ $message }}</p>@enderror
            <button class="button button-primary" type="submit">{{ __('moderation.confirm_setup') }}</button>
        </form>
    </section>
@endsection
