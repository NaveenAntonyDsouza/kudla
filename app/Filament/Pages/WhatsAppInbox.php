<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Profile;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppInboxService;
use App\Support\WhatsAppCloud;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * WhatsApp Inbox (Oct 2026): the office number's chats once it runs on the
 * WhatsApp Cloud API, when the WhatsApp app on the office phone no longer
 * works. Conversations on the left, the chat on the right; staff reply,
 * link an unknown number to a member, and add photos members send.
 *
 * Meta only allows free-text replies within 24 hours of the member's last
 * message; after that the reply box is closed until they write again.
 */
class WhatsAppInbox extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'WhatsApp Inbox';
    protected static ?string $title = 'WhatsApp Inbox';
    protected static \UnitEnum|string|null $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.whatsapp-inbox';

    #[Url(as: 'chat')]
    public ?string $selected = null;

    #[Url]
    public string $filter = 'all'; // all | unread | unknown

    public string $search = '';

    public string $reply = '';

    public string $linkMatriId = '';

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    public static function shouldRegisterNavigation(): bool
    {
        try {
            return static::canAccess() && (WhatsAppCloud::enabled() || WhatsAppMessage::query()->exists());
        } catch (\Throwable) {
            return false; // table not migrated yet on this site
        }
    }

    public function mount(): void
    {
        $this->markRead(); // opened from a link with ?chat=
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $unread = WhatsAppMessage::where('direction', WhatsAppMessage::IN)->whereNull('read_at')->distinct()->count('wa_id');
        } catch (\Throwable) {
            return null;
        }

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    /** One row per number, newest first: last message, unread count, member. */
    public function conversations(): Collection
    {
        $rows = DB::table('whatsapp_messages')
            ->select('wa_id')
            ->selectRaw('MAX(id) as last_id')
            ->selectRaw("SUM(CASE WHEN direction = 'in' AND read_at IS NULL THEN 1 ELSE 0 END) as unread")
            ->selectRaw('MAX(profile_id) as profile_id')
            ->selectRaw('MAX(contact_name) as contact_name')
            ->groupBy('wa_id');

        if ($this->filter === 'unread') {
            $rows->havingRaw("SUM(CASE WHEN direction = 'in' AND read_at IS NULL THEN 1 ELSE 0 END) > 0");
        } elseif ($this->filter === 'unknown') {
            $rows->havingRaw('MAX(profile_id) IS NULL');
        }

        $term = trim($this->search);
        if ($term !== '') {
            $digits = preg_replace('/\D/', '', $term);
            $profileIds = Profile::where('matri_id', \App\Support\MatriId::current($term))->orWhere('full_name', 'like', "%{$term}%")->limit(50)->pluck('id');
            $rows->where(function ($q) use ($digits, $term, $profileIds) {
                if (strlen($digits) >= 4) {
                    $q->orWhere('wa_id', 'like', "%{$digits}%");
                }
                $q->orWhere('contact_name', 'like', "%{$term}%")->orWhereIn('profile_id', $profileIds);
            });
        }

        $rows = $rows->orderByDesc('last_id')->limit(60)->get();

        $last = WhatsAppMessage::whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $profiles = Profile::whereIn('id', $rows->pluck('profile_id')->filter())->get(['id', 'matri_id', 'full_name'])->keyBy('id');

        return $rows->map(fn ($r) => (object) [
            'wa_id' => $r->wa_id,
            'unread' => (int) $r->unread,
            'name' => $profiles[$r->profile_id]->full_name ?? $r->contact_name ?? ('+'.$r->wa_id),
            'matri_id' => $profiles[$r->profile_id]->matri_id ?? null,
            'last' => $last[$r->last_id] ?? null,
        ]);
    }

    public function thread(): Collection
    {
        if (! $this->selected) {
            return collect();
        }

        return WhatsAppMessage::where('wa_id', $this->selected)->orderByDesc('id')->limit(150)->get()->reverse()->values();
    }

    public function selectedProfile(): ?Profile
    {
        if (! $this->selected) {
            return null;
        }
        $id = WhatsAppMessage::where('wa_id', $this->selected)->whereNotNull('profile_id')->latest('id')->value('profile_id');

        return $id ? Profile::find($id) : null;
    }

    /** Members whose numbers match this chat, to offer when it is not linked yet. */
    public function suggestedProfiles(): Collection
    {
        return $this->selected ? WhatsAppCloud::profilesForNumber($this->selected) : collect();
    }

    /** Hours left to send free text (Meta's 24-hour window), or null when closed. */
    public function windowHoursLeft(): ?int
    {
        $lastIn = $this->selected
            ? WhatsAppMessage::where('wa_id', $this->selected)->where('direction', WhatsAppMessage::IN)->max('created_at')
            : null;
        if (! $lastIn) {
            return null;
        }
        $closes = \Illuminate\Support\Carbon::parse($lastIn)->addDay();

        return $closes->isFuture() ? max(1, (int) ceil(now()->diffInMinutes($closes) / 60)) : null;
    }

    public function open(string $waId): void
    {
        $this->selected = preg_replace('/\D/', '', $waId);
        $this->reply = '';
        $this->linkMatriId = '';
        $this->markRead();
    }

    public function markRead(): void
    {
        if ($this->selected) {
            WhatsAppMessage::where('wa_id', $this->selected)->where('direction', WhatsAppMessage::IN)
                ->whereNull('read_at')->update(['read_at' => now()]);
        }
    }

    public function markUnread(): void
    {
        if ($this->selected) {
            $lastIn = WhatsAppMessage::where('wa_id', $this->selected)->where('direction', WhatsAppMessage::IN)->max('id');
            WhatsAppMessage::whereKey($lastIn)->update(['read_at' => null]);
            $this->selected = null;
        }
    }

    public function send(): void
    {
        $text = trim($this->reply);
        if (! $this->selected || $text === '') {
            return;
        }
        if (mb_strlen($text) > 4000) {
            Notification::make()->title('Message too long (4,000 characters at most)')->danger()->send();

            return;
        }
        if ($this->windowHoursLeft() === null) {
            Notification::make()->title('Reply window closed')
                ->body('WhatsApp only allows a free reply within 24 hours of the member\'s last message.')->warning()->send();

            return;
        }

        $message = app(WhatsAppInboxService::class)->send($this->selected, $text, auth()->id());
        if ($message->status === 'failed') {
            Notification::make()->title('Not sent')->body($message->note)->danger()->send();

            return;
        }

        $this->reply = '';
        $this->markRead();
    }

    /**
     * Outside the 24-hour window only an approved template can go out: this
     * one asks the member to reply, which opens the window again.
     */
    public function sendReplyWaiting(string $language = 'en'): void
    {
        if (! $this->selected || $this->windowHoursLeft() !== null || ! WhatsAppCloud::templatesEnabled()) {
            return;
        }

        $recent = WhatsAppMessage::where('wa_id', $this->selected)->where('direction', WhatsAppMessage::OUT)
            ->where('note', 'template:'.WhatsAppCloud::TEMPLATE_REPLY_WAITING)->where('created_at', '>=', now()->subDay())->exists();
        if ($recent) {
            Notification::make()->title('Already sent today')
                ->body('The member was asked to reply in the last 24 hours. Wait for their reply, or call them.')->warning()->send();

            return;
        }

        $profile = $this->selectedProfile();
        $name = $profile?->full_name ?? WhatsAppMessage::where('wa_id', $this->selected)->whereNotNull('contact_name')->latest('id')->value('contact_name');
        $message = app(WhatsAppInboxService::class)->sendTemplate(
            $this->selected,
            WhatsAppCloud::TEMPLATE_REPLY_WAITING,
            $language,
            [WhatsAppCloud::firstName($name)],
            auth()->id(),
            $profile?->id,
        );

        $message->status === 'failed'
            ? Notification::make()->title('Not sent')->body($message->note)->danger()->send()
            : Notification::make()->title('Sent. Their reply will open the chat again.')->success()->send();
    }

    public function linkMember(?string $matriId = null): void
    {
        $matriId = strtoupper(trim($matriId ?? $this->linkMatriId));
        $profile = $matriId !== '' ? Profile::where('matri_id', \App\Support\MatriId::current($matriId))->first() : null;
        if (! $this->selected || ! $profile) {
            Notification::make()->title('No member with that ID')->danger()->send();

            return;
        }

        WhatsAppMessage::where('wa_id', $this->selected)->update(['profile_id' => $profile->id]);
        $this->linkMatriId = '';
        Notification::make()->title("Linked to {$profile->matri_id}")->success()->send();
    }

    public function attachPhoto(int $messageId): void
    {
        $message = WhatsAppMessage::where('wa_id', $this->selected)->find($messageId);
        $profile = $this->selectedProfile();
        if (! $message || ! $message->media_id || $message->profile_photo_id) {
            return;
        }
        if (! $profile) {
            Notification::make()->title('Link this chat to a member first')->warning()->send();

            return;
        }

        try {
            $photo = app(WhatsAppInboxService::class)->attachByStaff($message, $profile, (int) auth()->id());
        } catch (\Throwable $e) {
            Notification::make()->title('Photo not added')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title("Added to {$profile->matri_id} as a {$photo->photo_type} photo")->success()->send();
    }

    public function profileUrl(Profile $profile): string
    {
        return UserResource::profileViewUrl($profile);
    }

    public function mediaUrl(WhatsAppMessage $message): string
    {
        return route('filament.admin.whatsapp-media', $message);
    }

    public function getSubheading(): ?string
    {
        return WhatsAppCloud::enabled() ? null : 'The WhatsApp API is switched off (Admin & Security → WhatsApp API). Messages below are from when it was on.';
    }
}
