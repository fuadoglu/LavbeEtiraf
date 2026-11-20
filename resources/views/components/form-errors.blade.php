@props(['fields'])
@if($errors->any())
    <section class="form-errors" tabindex="-1" autofocus aria-labelledby="form-errors-heading">
        <h2 id="form-errors-heading">{{ __('ui.check_fields') }}</h2>
        <ul>
            @foreach($errors->getMessages() as $field => $messages)
                @foreach($messages as $message)
                    <li>
                        @if(isset($fields[$field]))
                            <a href="#{{ $fields[$field] }}">{{ $message }}</a>
                        @else
                            {{ $message }}
                        @endif
                    </li>
                @endforeach
            @endforeach
        </ul>
    </section>
@endif
