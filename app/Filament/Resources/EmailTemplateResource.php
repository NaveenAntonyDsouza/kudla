<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Email Templates';
    protected static ?string $modelLabel = 'Email Template';
    protected static ?string $pluralModelLabel = 'Email Templates';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Template Info')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Used internally to identify this template. Cannot be changed.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive templates will fall back to the default Blade view.'),
                ])
                ->columns(3),

            \Filament\Schemas\Components\Section::make('Email Content')
                ->description('Use {{VARIABLE_NAME}} placeholders. Available variables are listed below the editor.')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->label('Subject Line')
                        ->required()
                        ->maxLength(255)
                        ->helperText('e.g., "New Interest Received - {{SITE_NAME}}"'),

                    Forms\Components\Textarea::make('body_html')
                        ->label('Email Body (HTML)')
                        ->required()
                        ->rows(15)
                        ->helperText('Write HTML email content. Use {{VARIABLE}} placeholders for dynamic data.')
                        ->columnSpanFull(),

                    Forms\Components\TagsInput::make('variables')
                        ->label('Available Variables')
                        ->helperText('These variables can be used as {{VARIABLE_NAME}} in subject and body. Common: SITE_NAME, SITE_URL, LOGIN_URL')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Template')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->limit(50)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('variables')
                    ->label('Variables')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' vars' : '0 vars')
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('sendTest')
                    ->label('Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Send Test Email')
                    ->modalDescription(fn (EmailTemplate $record) => 'Send a test email for "' . $record->name . '" to the admin email address?')
                    ->action(function (EmailTemplate $record) {
                        $adminEmail = auth()->user()?->email;
                        if (! $adminEmail) {
                            Notification::make()->title('No admin email found')->danger()->send();
                            return;
                        }

                        // Render with sample data
                        $sampleVars = collect($record->variables ?? [])->mapWithKeys(function ($var) {
                            return [$var => match ($var) {
                                'USER_NAME', 'SENDER_NAME', 'RECEIVER_NAME' => 'John Doe',
                                'MATRI_ID', 'SENDER_MATRI_ID', 'ACCEPTER_MATRI_ID', 'DECLINER_MATRI_ID' => 'AM100001',
                                'PLAN_NAME' => 'Gold Plan',
                                'EXPIRY_DATE' => now()->addMonths(6)->format('d M Y'),
                                'EXPIRY_MINUTES' => '60',
                                'REASON' => 'Sample rejection reason for testing',
                                'ACTION_URL' => url('/dashboard'),
                                'SITE_NAME' => config('app.name'),
                                'SITE_URL' => config('app.url'),
                                'LOGIN_URL' => url('/login'),
                                default => "[{$var}]",
                            }];
                        })->toArray();

                        $rendered = $record->render($sampleVars);

                        try {
                            Mail::html($rendered['body'], function ($message) use ($adminEmail, $rendered) {
                                $message->to($adminEmail)
                                    ->subject('[TEST] ' . $rendered['subject']);
                            });

                            Notification::make()
                                ->title('Test email sent to ' . $adminEmail)
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to send test email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }
}
