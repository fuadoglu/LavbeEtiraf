@extends('layouts.app')
@section('title', __('reports.appeal_title'))
@section('content')<section class="write-page"><h1>{{ __('reports.appeal_title') }}</h1><x-status tone="success">{{ __('reports.appeal_received') }}</x-status></section>@endsection
