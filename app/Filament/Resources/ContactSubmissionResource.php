<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactSubmissionResource\Pages;
use App\Models\ContactSubmission;
use App\Traits\LogsAdminActivity;
use BackedEnum;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class ContactSubmissionResource extends Resource
{
    use LogsAdminActivity;
    protected static ?string $model = ContactSubmission::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Contact Inbox';
    protected static ?string $modelLabel = 'Inquiry';
    protected static ?string $pluralModelLabel = 'Contact Inbox';
    protected static \UnitEnum|string|null $navigationGroup = 'Interests & Reports';
    protected static ?int $navigationSort = 4;

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

                Tables\Columns\TextColumn::make('user.profile.matri_id')
                    ->label('Matri ID')
                    ->placeholder('Guest')
                    ->url(fn (ContactSubmission $record) => $record->user?->profile
                        ? UserResource::getUrl('view', ['record' => $record->user->profile->id])
                        : null),

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
                    ->description(fn (ContactSubmission $record) =>
                        $record->created_at->diffInHours(now()) > 48 && $record->status === 'new'
                            ? 'OVERDUE'
                            : null
                    )
                    ->color(fn (ContactSubmission $record) =>
                        $record->created_at->diffInHours(now()) > 48 && $record->status === 'new'
                            ? 'danger'
                            : null
                    ),
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

                \Filament\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (ContactSubmission $record) => in_array($record->status, ['new', 'in_progress']))
                    ->form([
                        Forms\Components\Select::make('canned_response')
                            ->label('Quick Reply Template')
                            ->placeholder('Select a template to pre-fill...')
                            ->options(function () {
                                $responses = json_decode(\App\Models\SiteSetting::getValue('canned_responses', '[]'), true);
                                return collect($responses ?: [])->pluck('label', 'label')->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $responses = json_decode(\App\Models\SiteSetting::getValue('canned_responses', '[]'), true);
                                    $match = collect($responses ?: [])->firstWhere('label', $state);
                                    if ($match) {
                                        $set('admin_reply', $match['body']);
                                    }
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('admin_reply')
                            ->label('Reply Message')
                            ->required()
                            ->rows(5)
                            ->placeholder('Type your reply or select a template above...'),
                    ])
                    ->action(function (ContactSubmission $record, array $data) {
                        // Send reply email
                        try {
                            Mail::raw(
                                $data['admin_reply'] . "\n\n---\nOriginal inquiry: {$record->subject}\n\n" . config('app.name') . " Support",
                                function ($mail) use ($record) {
                                    $mail->to($record->email, $record->name)
                                        ->subject("Re: {$record->subject} - " . config('app.name'));
                                }
                            );
                        } catch (\Exception $e) {
                            \Log::error('Contact reply email failed: ' . $e->getMessage());
                        }

                        $record->update([
                            'admin_reply' => $data['admin_reply'],
                            'status' => 'replied',
                            'replied_at' => now(),
                        ]);

                        self::logActivity('contact_replied', $record);

                        Notification::make()
                            ->title('Reply sent to ' . $record->email)
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (ContactSubmission $record) => $record->status !== 'closed')
                    ->requiresConfirmation()
                    ->action(function (ContactSubmission $record) {
                        $record->update(['status' => 'closed']);
                        Notification::make()->title('Inquiry closed')->success()->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user.profile'));
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

            \Filament\Schemas\Components\Section::make('Admin Response')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_reply')->label('Reply Sent')->default('Not yet replied')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('replied_at')->label('Replied At')->dateTime('d M Y, h:i A')->default('-'),
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

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
