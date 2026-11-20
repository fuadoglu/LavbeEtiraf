@extends('layouts.app')

@section('title', __("reports.{$channel}_title").' · Lavbe')

@section('content')
    <section class="write-page">
        <header class="page-heading">
            <h1>{{ __("reports.{$channel}_title") }}</h1>
            <p>{{ __('reports.'.($comment ? 'target_comment' : 'target_confession')) }}: {{ $comment ? $confession->author_alias : ($confession->title ?: __('lavbe.untitled_confession')) }}</p>
        </header>
        @if($channel === 'legal')
            <x-status tone="warning">{{ __('reports.legal_separation') }}</x-status>
        @endif
        <form method="post" action="{{ route('reports.store', ['locale' => $lavbeLocale['segment'], 'channel' => $channel, 'type' => $targetType, 'targetPublicId' => $targetPublicId]) }}" class="write-form">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            <x-form-field :label="__('reports.reason')" name="reason_code" :error="$errors->first('reason_code')">
                <select @if($errors->has('reason_code')) aria-describedby="reason_code-description" @endif aria-invalid="{{ $errors->has('reason_code') ? 'true' : 'false' }}" id="reason_code" name="reason_code" required>
                    @if($channel === 'legal')
                        @foreach(['privacy', 'copyright', 'court_order', 'other'] as $reason)
                            <option value="{{ $reason }}" @selected(old('reason_code') === $reason)>{{ __('reports.legal_'.$reason) }}</option>
                        @endforeach
                    @else
                        @foreach(['spam', 'privacy', 'harassment', 'threat', 'explicit', 'other'] as $reason)
                            <option value="{{ $reason }}" @selected(old('reason_code') === $reason)>{{ __('reports.reason_'.$reason) }}</option>
                        @endforeach
                    @endif
                </select>
            </x-form-field>
            <x-form-field :label="__('reports.'.($channel === 'legal' ? 'legal_details' : 'details'))" name="details" :hint="__('reports.details_hint')" :error="$errors->first('details')">
                <textarea aria-describedby="details-description" aria-invalid="{{ $errors->has('details') ? 'true' : 'false' }}" id="details" name="details" maxlength="{{ config('lavbe.limits.report_details') }}" @required($channel === 'legal')>{{ old('details') }}</textarea>
            </x-form-field>
            <button class="button button-primary" type="submit">{{ __('reports.submit'.($channel === 'legal' ? '_legal' : '')) }}</button>
        </form>
        @if($channel === 'community')
            <a href="{{ route('reports.legal.create', ['locale' => $lavbeLocale['segment'], 'type' => $targetType, 'targetPublicId' => $targetPublicId]) }}">{{ __('reports.legal_title') }}</a>
        @endif
    </section>
@endsection
