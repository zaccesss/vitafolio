<x-help-page slug="support-tickets">
    <h2>{{ __('Opening a ticket') }}</h2>
    <p>{!! __('Go to <strong>Support</strong> in the footer and choose <strong>Open a support ticket</strong>. Pick what it is about, add a subject and describe the problem. You do not need an account: without one, give your name and email address.') !!}</p>
    <ul>
        <li>{!! __('Messages can use Markdown: <code>**bold**</code>, <code>*italics*</code>, <code>`code`</code>, lists and links. A counter shows how many of the 5,000 characters you have used.') !!}</li>
        <li>{{ __('Attach up to three screenshots of 5 MB each. Only you and the support team can see them.') }}</li>
        <li>{{ __('Never include a password or a sign-in code. Nobody who works on Vitafolio will ever ask for one.') }}</li>
    </ul>

    <h2>{{ __('Following the conversation') }}</h2>
    <p>{!! __('Each ticket gets a reference such as VF-1042 and a confirmation email. Signed in, find every ticket under <strong>Your tickets</strong> in your account menu. Without an account, use the private link in the email: keep it, as it is the only way to reach the ticket.') !!}</p>
    <p>{{ __('You are emailed when the support team replies. A ticket shows Open while it waits for the team, Waiting on you after a reply and Resolved once it is sorted. Replying reopens it.') }}</p>

    <h2>{{ __('What happens to tickets') }}</h2>
    <p>{{ __('A resolved ticket closes after 14 days without a reply. A closed ticket is deleted with its screenshots one year after its last message. Deleting your account deletes its tickets straight away.') }}</p>
</x-help-page>
