<x-layouts.app :title="__('Open a support ticket')" :turnstile="! $user">
    <div class="container-page py-10">
        <div class="max-w-2xl">
            <h1 class="text-3xl">{{ __('Open a support ticket') }}</h1>
            <p class="mt-2 text-muted">{{ __('Tell us what is wrong and we will reply by email, usually within a few working days. You can follow the conversation and reply on the ticket page.') }}</p>
            <x-error-summary />
            <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="card mt-6 grid gap-4 p-6">
                @csrf
                @guest
                    <x-field name="name" :label="__('Your name')" required autocomplete="name" maxlength="100" />
                    <x-field name="email" :label="__('Your email')" type="email" required autocomplete="email" maxlength="254" :hint="__('Replies go here, with a private link to your ticket.')" />
                @endguest
                <x-field name="category" :label="__('What is it about?')" type="select" required :options="\App\Models\SupportTicket::categoryLabels()" />
                <x-field name="subject" :label="__('Subject')" required maxlength="150" />
                @include('support._compose', ['label' => __('Describe the problem')])
                {{-- hidden from people; only bots fill it in --}}
                <div class="hidden" aria-hidden="true"><label>{{ __('Website') }} <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                @guest <x-turnstile /> @endguest
                <div><button type="submit" class="btn btn-primary">{{ __('Open ticket') }}</button></div>
            </form>
            @auth
                <p class="mt-4"><a href="{{ route('support.index') }}">{{ __('See your tickets') }}</a></p>
            @endauth
        </div>
    </div>
</x-layouts.app>
