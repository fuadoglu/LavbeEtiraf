<form method="post" action="{{ route('reports.store', ['locale' => $locale, 'channel' => $channel, 'type' => $targetType, 'targetPublicId' => $targetPublicId]) }}" class="write-form">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    <x-form-field :label="__('reports.reason')" name="reason_code" :id="'reason_code-'.$channel" :error="$errors->first('reason_code')">
        <select @if($errors->has('reason_code')) aria-describedby="reason_code-{{ $channel }}-description" @endif aria-invalid="{{ $errors->has('reason_code') ? 'true' : 'false' }}" id="reason_code-{{ $channel }}" name="reason_code" required>
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
    <x-form-field :label="__('reports.'.($channel === 'legal' ? 'legal_details' : 'details'))" name="details" :hint="__('reports.details_hint')" :id="'details-'.$channel" :error="$errors->first('details')">
        <textarea @if($errors->has('details')) aria-describedby="details-{{ $channel }}-description" @elseif(__('reports.details_hint')) aria-describedby="details-{{ $channel }}-description" @endif aria-invalid="{{ $errors->has('details') ? 'true' : 'false' }}" id="details-{{ $channel }}" name="details" maxlength="{{ config('lavbe.limits.report_details') }}" @required($channel === 'legal')>{{ old('details') }}</textarea>
    </x-form-field>
    <button class="button button-primary" type="submit">{{ __('reports.submit'.($channel === 'legal' ? '_legal' : '')) }}</button>
</form>
