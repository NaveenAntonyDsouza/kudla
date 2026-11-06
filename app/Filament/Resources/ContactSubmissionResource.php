<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactSubmissionResource\Pages;
use App\Models\ContactSubmission;
use App\Models\SiteSetting;
use App\Traits\LogsAdminActivity;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/*
| Contact Inbox. 6 Nov 2026: messages are grouped by sender (email address). Most
| repeats were the same message sent several times within a minute, and on Crasta
| bots sent the same link 5 times with a different phone number each time. The New
| tab shows one row per sender with how many messages wait and since when; Reply
| and Close answer all of the sender's waiting messages; the menu badge, the Work
| Queue and the Scorecard count people waiting.
*/
class ContactSubmissionResource extends Resource
{
    use LogsAdminActivity;
    protected static ?string $model = ContactSubmission::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Contact Inbox';
    protected static ?string $modelLabel = 'Inquiry';
    protected static ?string $pluralModelLabel = 'Contact Inbox';
    protected static \UnitEnum|string|null $navigationGroup = 'Today';
    protected static ?int $navigationSort = 5;

    /** A waiting message is overdue after this many hours (the Work Queue's target is 1 day). */
    private const OVERDUE_HOURS = 48;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('reply_contact');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('reply_contact');
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canDelete($record): bool
    {
        return static::canAccess();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(40),

                // how many of the sender's messages wait (the New tab shows one row per sender)
                Tables\Columns\TextColumn::make('sender_waiting')
                    ->label('Waiting')
                    ->getStateUsing(fn (ContactSubmission $record): ?string => $record->isOpen() && (int) $record->sender_waiting > 0
                        ? $record->sender_waiting . ' ' . Str::plural('message', (int) $record->sender_waiting)
                        : null)
                    ->badge()
                    ->color(fn (ContactSubmission $record): string => (int) $record->sender_waiting > 1 ? 'warning' : 'gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.profile.matri_id')
                    ->label('Matri ID')
                    ->placeholder('Guest')
                    ->url(fn (ContactSubmission $record) => UserResource::profileViewUrl($record->user?->profile)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'danger',
                        'in_progress' => 'warning',
                        'replied' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable()
                    ->description(fn (ContactSubmission $record): ?string => self::waitingNote($record))
                    ->color(fn (ContactSubmission $record): ?string => self::isOverdue($record) ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'in_progress' => 'In Progress',
                        'replied' => 'Replied',
                        'closed' => 'Closed',
                    ]),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                self::replyAction(),
                self::closeAction(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user.profile')->withSenderWaiting());
    }

    /**
     * Reply by email. One reply answers all of the sender's waiting messages (the popup
     * shows them); they are marked replied only when the email went out.
     */
    public static function replyAction(): Action
    {
        return Action::make('reply')
            ->label('Reply')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->visible(fn (ContactSubmission $record): bool => $record->isOpen())
            ->modalHeading(fn (ContactSubmission $record): string => 'Reply to ' . $record->name)
            ->modalDescription(fn (ContactSubmission $record): string => 'By email to ' . $record->email . '.')
            ->schema([
                SchemaView::make('filament.contact.waiting-messages')
                    ->viewData(fn (ContactSubmission $record): array => ['messages' => $record->openFromSender()]),

                Forms\Components\Select::make('canned_response')
                    ->label('Quick Reply Template')
                    ->placeholder('Select a template to pre-fill...')
                    ->options(fn (): array => collect(self::cannedResponses())->pluck('label', 'label')->toArray())
                    ->live()
                    // Set is the Filament 5 class (6 Nov 2026: the old Forms\Set no longer exists, so
                    // picking a template stopped the popup with an error)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $match = $state ? collect(self::cannedResponses())->firstWhere('label', $state) : null;
                        if ($match) {
                            $set('admin_reply', $match['body']);
                        }
                    })
                    ->dehydrated(false),

                Forms\Components\Textarea::make('admin_reply')
                    ->label('Reply Message')
                    ->required()
                    ->rows(5)
                    ->placeholder('Type your reply or select a template above...'),
            ])
            ->action(function (ContactSubmission $record, array $data): void {
                $messages = $record->openFromSender();

                try {
                    Mail::raw(
                        $data['admin_reply'] . "\n\n---\nOriginal inquiry: {$record->subject}\n\n" . config('app.name') . ' Support',
                        function ($mail) use ($record) {
                            $mail->to($record->email, $record->name)
                                ->subject("Re: {$record->subject} - " . config('app.name'));
                        }
                    );
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->danger()
                        ->title('The reply could not be sent')
                        ->body('Nothing was marked as replied. Try again later, or answer by phone and use Close.')
                        ->send();

                    return;
                }

                foreach ($messages as $message) {
                    $message->update([
                        'admin_reply' => $data['admin_reply'],
                        'status' => 'replied',
                        'replied_at' => now(),
                    ]);
                }

                self::logActivity('contact_replied', $record, ['messages' => $messages->count(), 'ids' => $messages->modelKeys()]);

                Notification::make()
                    ->title('Reply sent to ' . $record->email)
                    ->body($messages->count() > 1 ? "It answers all {$messages->count()} of their messages." : null)
                    ->success()
                    ->send();
            });
    }

    /**
     * Close: after answering another way (phone, WhatsApp), or for spam. A waiting
     * message closes together with the sender's other waiting messages.
     */
    public static function closeAction(): Action
    {
        return Action::make('close')
            ->label('Close')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->visible(fn (ContactSubmission $record): bool => $record->status !== 'closed')
            ->requiresConfirmation()
            ->modalHeading(fn (ContactSubmission $record): string => ($n = self::closedTogether($record)->count()) > 1
                ? "Close all {$n} messages from {$record->name}?"
                : 'Close this message?')
            ->modalDescription(function (ContactSubmission $record): string {
                $messages = self::closedTogether($record);
                $when = fn (ContactSubmission $m): string => $m->created_at->displayTz()->format('j M Y');

                return 'Use this after answering another way (phone, WhatsApp), or for spam.'
                    . ($messages->count() > 1 ? " They came in between {$when($messages->first())} and {$when($messages->last())}." : '');
            })
            ->modalSubmitActionLabel('Close')
            ->action(function (ContactSubmission $record): void {
                $messages = self::closedTogether($record);
                foreach ($messages as $message) {
                    $message->update(['status' => 'closed']);
                }

                self::logActivity('contact_closed', $record, ['messages' => $messages->count(), 'ids' => $messages->modelKeys()]);

                Notification::make()
                    ->title($messages->count() > 1 ? "{$messages->count()} messages closed" : 'Inquiry closed')
                    ->success()
                    ->send();
            });
    }

    /** What Close closes: a waiting message with the sender's other waiting ones, anything else on its own. */
    private static function closedTogether(ContactSubmission $record): Collection
    {
        return $record->isOpen() ? $record->openFromSender() : new Collection([$record]);
    }

    /** @return list<array{label: string, body: string}> */
    private static function cannedResponses(): array
    {
        $responses = json_decode((string) SiteSetting::getValue('canned_responses', '[]'), true);

        return array_values(array_filter(is_array($responses) ? $responses : [],
            fn ($r): bool => is_array($r) && filled($r['label'] ?? null) && isset($r['body'])));
    }

    /** When the sender's first waiting message came in (null: this message is not waiting). */
    private static function waitingSince(ContactSubmission $record): ?Carbon
    {
        if (! $record->isOpen()) {
            return null;
        }

        return filled($record->sender_waiting_since) ? Carbon::parse($record->sender_waiting_since) : $record->created_at;
    }

    private static function isOverdue(ContactSubmission $record): bool
    {
        $since = self::waitingSince($record);

        return $since !== null && $since->lt(now()->subHours(self::OVERDUE_HOURS));
    }

    /** "waiting since 26 May 2026 · OVERDUE" under the Received time. */
    private static function waitingNote(ContactSubmission $record): ?string
    {
        $since = self::waitingSince($record);
        if ($since === null) {
            return null;
        }

        $note = [];
        if ($since->lt($record->created_at->copy()->subMinute())) {
            $note[] = 'waiting since ' . $since->displayTz()->format('j M Y');
        }
        if (self::isOverdue($record)) {
            $note[] = 'OVERDUE';
        }

        return $note !== [] ? implode(' · ', $note) : null;
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema([
            \Filament\Schemas\Components\Section::make('Inquiry Details')
                ->schema([
                    Infolists\Components\TextEntry::make('name')->label('Name'),
                    Infolists\Components\TextEntry::make('email')->label('Email'),
                    Infolists\Components\TextEntry::make('phone')->label('Phone')->default('-'),
                    Infolists\Components\TextEntry::make('user.profile.matri_id')->label('Matri ID')->default('Guest'),
                    Infolists\Components\TextEntry::make('subject')->label('Subject')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('message')->label('Message')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'new' => 'danger',
                            'in_progress' => 'warning',
                            'replied' => 'success',
                            'closed' => 'gray',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('created_at')->label('Received')->dateTime('d M Y, h:i A'),
                    Infolists\Components\TextEntry::make('ip_address')->label('IP Address')->color('gray'),
                ])
                ->columns(3),

            // everything the same sender sent (6 Nov 2026: up to 11 messages from one address)
            \Filament\Schemas\Components\Section::make('All messages from this sender')
                ->schema([
                    SchemaView::make('filament.contact.sender-messages')
                        ->viewData(fn (ContactSubmission $record): array => ['messages' => $record->allFromSender(), 'current' => $record->getKey()]),
                ])
                ->visible(fn (ContactSubmission $record): bool => ContactSubmission::fromSender((string) $record->email)->count() > 1),

            \Filament\Schemas\Components\Section::make('Admin Response')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_reply')->label('Reply Sent')->default('Not yet replied')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('replied_at')->label('Replied At')->dateTime('d M Y, h:i A')->placeholder('-'),
                    Infolists\Components\TextEntry::make('admin_notes')->label('Internal Notes')->default('-')->columnSpanFull(),
                ])
                ->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactSubmissions::route('/'),
            'view' => Pages\ViewContactSubmission::route('/{record}'),
        ];
    }

    /** People waiting for an answer, not messages (one person may have sent several). */
    public static function getNavigationBadge(): ?string
    {
        return (string) ContactSubmission::sendersWaiting() ?: null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $messages = ContactSubmission::where('status', 'new')->count();
        $people = ContactSubmission::sendersWaiting();

        return $messages > 0
            ? $messages . ' ' . Str::plural('message', $messages) . ' from ' . $people . ' ' . Str::plural('person', $people)
            : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
