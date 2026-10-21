New WhatsApp message

From: {!! $sender !!}
Number: +{{ $whatsappMessage->wa_id }}
Received: {{ $whatsappMessage->created_at?->timezone('Asia/Kolkata')->format('j M Y, g:i a') }}

{!! $preview !!}

Open the chat and reply:
{!! $inboxUrl !!}

WhatsApp only allows a free reply within 24 hours of the member's message.
More messages from this number in the next {{ $quietMinutes }} minutes will not send another email.

---
{{ \App\Models\SiteSetting::getValue('site_name', config('app.name')) }} admin alert. Turn it off in Settings -> WhatsApp API.
