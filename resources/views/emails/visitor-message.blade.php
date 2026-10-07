{{-- plain text, so values print raw: html escaping would turn an apostrophe into &#039; --}}
{!! __(':heading from :name', ['heading' => __($heading, ['app' => config('app.name')]), 'name' => $data['sender_name']]) !!}

{!! __('Name: :name', ['name' => $data['sender_name']]) !!}
{!! __('Email: :email', ['email' => $data['sender_email']]) !!}
@if ($cvUrl)
{!! __('CV: :url', ['url' => $cvUrl]) !!}
@endif

{!! $data['message'] !!}

--
@if ($cvUrl)
{!! __('Reply to this email to answer them directly. Your own address stays hidden until you do.') !!}
@else
{!! __('Reply to this email to answer them directly.') !!}
@endif
{!! __('Sent through :app: :url', ['app' => config('app.name'), 'url' => config('app.url')]) !!}
