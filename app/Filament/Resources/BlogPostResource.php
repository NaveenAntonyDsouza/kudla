<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Blog Posts';
    protected static ?string $modelLabel = 'Blog Post';
    protected static ?string $pluralModelLabel = 'Blog Posts';
    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /** Blocks direct URL access too, and goes away when the blog is switched off. */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_content') && BlogPost::enabled();
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

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Schemas\Components\Section::make('Article')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(200)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('slug')
                        ->label('Web address')
                        ->required()
                        ->maxLength(120)
                        ->unique(ignoreRecord: true)
                        ->prefix('/blog/')
                        ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                        ->helperText('Lower-case words joined by hyphens. Avoid changing it after publishing: old links would break.'),

                    Forms\Components\Select::make('category')
                        ->label('Category')
                        ->options(array_combine(BlogPost::CATEGORIES, BlogPost::CATEGORIES)),

                    Forms\Components\Textarea::make('excerpt')
                        ->label('Short summary')
                        ->rows(2)
                        ->maxLength(300)
                        ->helperText('One or two sentences shown on the blog list. Leave blank to use the opening of the article.')
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('body')
                        ->label('Article')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'link', 'h2', 'h3',
                            'bulletList', 'orderedList', 'blockquote', 'table',
                            'undo', 'redo',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('Picture & links')
                ->schema([
                    Forms\Components\FileUpload::make('cover_image')
                        ->label('Cover picture')
                        ->image()
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('blog')
                        ->helperText('Wide picture, about 1200 × 675. Use only photos you own or have permission to use.'),

                    Forms\Components\TextInput::make('cover_alt')
                        ->label('Picture description')
                        ->maxLength(255)
                        ->helperText('What the picture shows, for blind readers and Google.'),

                    Forms\Components\TextInput::make('related_url')
                        ->label('Link at the end (optional)')
                        ->maxLength(255)
                        ->placeholder('/discover/catholic-matrimony')
                        ->rules(['regex:#^(/|https?://)#'])
                        ->helperText('A page on this site, e.g. a community page.'),

                    Forms\Components\TextInput::make('related_label')
                        ->label('Link text')
                        ->maxLength(100)
                        ->placeholder('See Catholic profiles'),

                    Forms\Components\TextInput::make('author_name')
                        ->label('Author')
                        ->maxLength(100)
                        ->helperText('Leave blank to show "' . \App\Models\SiteSetting::getValue('site_name', 'Site') . ' Team".'),
                ])
                ->columns(2)
                ->collapsible(),

            \Filament\Schemas\Components\Section::make('Google')
                ->description('Leave blank to use the title and summary.')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Google title')
                        ->maxLength(70),

                    Forms\Components\Textarea::make('meta_description')
                        ->label('Google description')
                        ->rows(2)
                        ->maxLength(160),
                ])
                ->collapsible()
                ->collapsed(),

            \Filament\Schemas\Components\Section::make('Publishing')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(['draft' => 'Draft', 'published' => 'Published'])
                        ->default('draft')
                        ->required(),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Publish on')
                        ->seconds(false)
                        ->helperText('A future date schedules the article. Blank = now, when published.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(70)
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->state(fn (BlogPost $record): string => $record->status !== 'published' ? 'Draft' : ($record->isLive() ? 'Published' : 'Scheduled'))
                    ->color(fn (string $state): string => match ($state) {
                        'Published' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publish date')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published']),
                Tables\Filters\SelectFilter::make('category')
                    ->options(array_combine(BlogPost::CATEGORIES, BlogPost::CATEGORIES)),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->url(fn (BlogPost $record): string => $record->url)
                    ->openUrlInNewTab(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('published_at', 'desc');
    }

    /** "Published" with no date means now. */
    public static function withPublishDate(array $data): array
    {
        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
