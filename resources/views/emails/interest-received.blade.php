<x-mail::message>
# New Interest Received

Dear {{ $receiverName }},

**{{ $senderMatriId }}** has expressed interest in your profile on {{ $siteName }}.

Log in to view their profile and respond to the interest.

<x-mail::button :url="$url">
View Interest
</x-mail::button>

Wishing you the best in your search,<br>
{{ $siteName }}
</x-mail::message>
