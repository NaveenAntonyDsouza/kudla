<x-mail::message>
# Great News! Interest Accepted

Dear {{ $senderName }},

**{{ $accepterMatriId }}** has accepted your interest on {{ $siteName }}!

You can now start a conversation and get to know each other better.

<x-mail::button :url="$url">
Start Chatting
</x-mail::button>

Wishing you the best,<br>
{{ $siteName }}
</x-mail::message>
