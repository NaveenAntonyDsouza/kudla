<x-mail::message>
# Interest Update

Dear {{ $senderName }},

{{ $declinerMatriId }} has reviewed your interest but has decided not to proceed at this time.

Don't be discouraged! There are many compatible profiles waiting for you.

<x-mail::button :url="$url">
Browse More Profiles
</x-mail::button>

Wishing you the best in your search,<br>
{{ $siteName }}
</x-mail::message>
