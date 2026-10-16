<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\SiteSetting;
use App\Support\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Members without a photo (Oct 2026, Kudla first): 66% of active profiles had
 * none, and the automatic reminder emails were not moving them. Staff open a
 * ready-made WhatsApp message from the office phone, mark the member as asked
 * (a note in the member's interaction history), and upload the photo the
 * member sends back. A member leaves the list once a photo is added.
 *
 * Shown where "Photo by WhatsApp at registration" is on, since the message
 * asks the member to send the photo on WhatsApp.
 */
class MembersWithoutPhoto extends Page implements HasTable
{
    use InteractsWithTable;

    /** Notes written by "Mark as asked" start with this, so the list can find them. */
    public const NOTE_PREFIX = 'Photo request:';

    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Members Without Photo';
    protected static ?string $title = 'Members Without Photo';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 8;
    protected string $view = 'filament.pages.members-without-photo';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member')
            && SiteSetting::getValue('photo_via_whatsapp', '0') === '1';
    }

    public static function query(): Builder
    {
        return Profile::query()
            ->where('is_active', true)
            ->whereNotNull('full_name')
            ->whereHas('user', fn ($q) => $q->whereNull('staff_role_id'))
            // no approved photo, and none waiting for approval (a rejected one needs replacing)
            ->whereDoesntHave('profilePhotos', fn ($q) => $q->whereIn('approval_status', [\App\Models\ProfilePhoto::STATUS_APPROVED, \App\Models\ProfilePhoto::STATUS_PENDING]))
            ->forUserBranch(null, true)
            ->with(['user', 'contactInfo', 'religiousInfo'])
            ->addSelect(['photo_asked_at' => ProfileNote::query()
                ->selectRaw('max(created_at)')
                ->whereColumn('profile_notes.profile_id', 'profiles.id')
                ->where('note', 'like', self::NOTE_PREFIX . '%')]);
    }

    /** The member's WhatsApp number, else their phone, as wa.me digits. */
    public static function whatsappDigits(Profile $profile): ?string
    {
        foreach ([$profile->contactInfo?->whatsapp_number, $profile->contactInfo?->primary_phone, $profile->user?->phone] as $number) {
            if ($number && ($digits = WhatsApp::digits($number))) {
                return $digits;
            }
        }

        return null;
    }

    /** The ready-made message: first name, member ID, reply with a photo. */
    public static function message(Profile $profile): string
    {
        $site = SiteSetting::getValue('site_name', 'Matrimony');
        $first = trim(strtok((string) $profile->full_name, ' ')) ?: 'there';

        return "Hi {$first}, greetings from {$site}! Profiles with a photo get many more responses from families. "
            . "Please reply to this message with one clear, recent photo of yourself, and we will add it to your profile ({$profile->matri_id}) for you. "
            . 'You can also add it yourself here: ' . route('photos.manage');
    }

    public static function whatsappUrl(Profile $profile): ?string
    {
        $digits = static::whatsappDigits($profile);

        return $digits ? 'https://wa.me/' . $digits . '?text=' . rawurlencode(static::message($profile)) : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('matri_id')
                    ->label('Member ID')
                    ->searchable()
                    ->url(fn (Profile $record) => UserResource::profileViewUrl($record)),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('religiousInfo.caste')
                    ->label('Community')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('WhatsApp / phone')
                    ->state(fn (Profile $record) => static::whatsappDigits($record) ? '+' . static::whatsappDigits($record) : null)
                    ->placeholder('No number'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('photo_asked_at')
                    ->label('Asked')
                    ->state(fn (Profile $record) => $record->photo_asked_at ? \Illuminate\Support\Carbon::parse($record->photo_asked_at)->format('j M Y') : null)
                    ->placeholder('Not yet')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('asked')
                    ->label('Asked for a photo')
                    ->placeholder('All')
                    ->trueLabel('Already asked')
                    ->falseLabel('Not asked yet')
                    ->queries(
                        true: fn (Builder $q) => $q->whereHas('profileNotes', fn ($n) => $n->where('note', 'like', self::NOTE_PREFIX . '%')),
                        false: fn (Builder $q) => $q->whereDoesntHave('profileNotes', fn ($n) => $n->where('note', 'like', self::NOTE_PREFIX . '%')),
                    ),
                Tables\Filters\SelectFilter::make('gender')
                    ->options(['Male' => 'Male', 'Female' => 'Female']),
                Tables\Filters\Filter::make('recent')
                    ->label('Joined in the last 30 days')
                    ->query(fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(30))),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->color('success')
                    ->url(fn (Profile $record) => static::whatsappUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Profile $record) => static::whatsappUrl($record) !== null),
                Action::make('markAsked')
                    ->label(fn (Profile $record) => $record->photo_asked_at ? 'Asked again' : 'Mark as asked')
                    ->color('gray')
                    ->action(function (Profile $record): void {
                        ProfileNote::create([
                            'profile_id' => $record->id,
                            'admin_user_id' => auth()->id(),
                            'note_type' => 'whatsapp',
                            'note' => self::NOTE_PREFIX . ' asked on WhatsApp to send a profile photo.',
                        ]);
                        Notification::make()->title("Marked {$record->matri_id} as asked")->success()->send();
                    }),
                Action::make('profile')
                    ->label('Open profile')
                    ->color('gray')
                    ->url(fn (Profile $record) => UserResource::profileViewUrl($record)),
            ])
            ->emptyStateHeading('Every active member has a photo')
            ->paginated([25, 50, 100]);
    }

    public function getSubheading(): ?string
    {
        $total = static::query()->count();
        $asked = static::query()->whereHas('profileNotes', fn ($n) => $n->where('note', 'like', self::NOTE_PREFIX . '%'))->count();

        return "{$total} active members have no photo; {$asked} have been asked. Click WhatsApp to open a ready-made message on this phone or computer (WhatsApp Web), send it from the office number, then mark the member as asked. When they reply with a photo, open their profile and upload it.";
    }
}
