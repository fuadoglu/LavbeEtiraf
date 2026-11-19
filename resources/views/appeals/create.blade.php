@extends('layouts.app')
@section('title', __('reports.appeal_title'))
@section('content')
<section class="write-page"><h1>{{ __('reports.appeal_title') }}</h1><p>{{ __('reports.appeal_intro') }}</p>
<form class="write-form" method="post" action="{{ route('appeals.store', ['locale' => request()->route('locale'), 'type' => $type, 'targetPublicId' => $publicId]) }}">@csrf
<input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
<x-form-field :label="__('reports.appeal_details')" name="details"><textarea name="details" rows="6" minlength="{{ config('lavbe.limits.appeal_details_min') }}" maxlength="{{ config('lavbe.limits.appeal_details') }}" required>{{ old('details') }}</textarea></x-form-field>
<button class="button button-primary" type="submit">{{ __('reports.appeal_submit') }}</button></form></section>
@endsection
