@extends('layouts.app')

@section('title', __('guest.write_title'))

@section('content')
    <x-moderation-shell :title="__('guest.write_title')">
        <section class="moderation-compose">
            @include('confessions._form', ['formAction' => route('moderation.confessions.store', ['locale' => request()->route('locale')])])
        </section>
    </x-moderation-shell>
@endsection
