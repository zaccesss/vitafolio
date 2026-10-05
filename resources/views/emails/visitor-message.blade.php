{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! $heading !!} from {!! $data['sender_name'] !!}

Name: {!! $data['sender_name'] !!}
Email: {!! $data['sender_email'] !!}
@if ($cvUrl)
CV: {!! $cvUrl !!}
@endif

{!! $data['message'] !!}

--
@if ($cvUrl)
Reply to this email to answer them directly. Your own address stays hidden until you do.
@else
Reply to this email to answer them directly.
@endif
Sent through {!! config('app.name') !!}: {!! config('app.url') !!}
