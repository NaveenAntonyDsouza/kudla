<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppInboxService;
use App\Support\WhatsAppCloud;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Meta's WhatsApp Cloud API webhook (Oct 2026).
 *
 * GET is Meta's one-time check when the webhook is saved: echo the challenge
 * if the verify token matches. POST carries incoming messages and delivery
 * statuses, signed with the app secret. Messages are stored and answered
 * after the response is sent, so Meta gets its 200 straight away.
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $token = WhatsAppCloud::verifyToken();

        if ($request->query('hub_mode') === 'subscribe' && $token !== ''
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): Response
    {
        if (! WhatsAppCloud::validSignature($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 403);
        }
        if (! WhatsAppCloud::enabled()) {
            return response('OK', 200); // switched off: acknowledge so Meta does not retry
        }

        $new = [];
        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);
                $names = collect($value['contacts'] ?? [])->pluck('profile.name', 'wa_id');

                foreach ((array) ($value['messages'] ?? []) as $msg) {
                    if ($stored = $this->store($msg, $names)) {
                        $new[] = $stored->id;
                    }
                }
                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    $this->updateStatus($status);
                }
            }
        }

        foreach ($new as $id) {
            dispatch(function () use ($id) {
                // Exactly once per message, whatever runs the after-response callbacks.
                if (! \Illuminate\Support\Facades\Cache::add('wa-processed:'.$id, 1, now()->addDay())) {
                    return;
                }
                if ($message = WhatsAppMessage::find($id)) {
                    $inbox = app(WhatsAppInboxService::class);
                    try {
                        $inbox->process($message);
                    } finally {
                        // After processing, so the email can say what happened to a photo.
                        $inbox->alertStaff($message->fresh() ?? $message);
                    }
                }
            })->afterResponse();
        }

        return response('OK', 200);
    }

    /** Save an incoming message; null when Meta re-sent one we already have. */
    private function store(array $msg, $names): ?WhatsAppMessage
    {
        $id = (string) ($msg['id'] ?? '');
        $from = preg_replace('/\D/', '', (string) ($msg['from'] ?? ''));
        if ($id === '' || $from === '' || WhatsAppMessage::where('wa_message_id', $id)->exists()) {
            return null;
        }

        $type = (string) ($msg['type'] ?? 'unknown');
        $media = in_array($type, ['image', 'document', 'video', 'audio', 'sticker'], true) ? (array) ($msg[$type] ?? []) : [];

        $body = match ($type) {
            'text' => $msg['text']['body'] ?? null,
            'button' => $msg['button']['text'] ?? null,
            'interactive' => $msg['interactive']['button_reply']['title'] ?? $msg['interactive']['list_reply']['title'] ?? null,
            default => $media['caption'] ?? null,
        };

        return WhatsAppMessage::create([
            'direction' => WhatsAppMessage::IN,
            'wa_message_id' => $id,
            'wa_id' => $from,
            'contact_name' => isset($names[$from]) ? mb_substr((string) $names[$from], 0, 150) : null,
            'type' => mb_substr($type, 0, 20),
            'body' => $body,
            'media_id' => $media['id'] ?? null,
            'media_mime' => isset($media['mime_type']) ? mb_substr((string) $media['mime_type'], 0, 100) : null,
            'status' => 'received',
        ]);
    }

    /** Delivery receipts for messages we sent: sent, delivered, read, failed. */
    private function updateStatus(array $status): void
    {
        $id = (string) ($status['id'] ?? '');
        $state = (string) ($status['status'] ?? '');
        if ($id === '' || ! in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) {
            return;
        }

        $message = WhatsAppMessage::where('wa_message_id', $id)->where('direction', WhatsAppMessage::OUT)->first();
        if (! $message) {
            return;
        }

        $order = ['sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];
        if (($order[$state] ?? 0) <= ($order[$message->status] ?? 0)) {
            return; // receipts can arrive out of order; never step back from "read"
        }

        $update = ['status' => $state];
        if ($state === 'failed') {
            $error = $status['errors'][0] ?? [];
            $update['note'] = trim(($message->note ? $message->note.': ' : '').'not delivered, '.($error['title'] ?? $error['message'] ?? 'unknown error'));
        }
        $message->update($update);
    }
}
