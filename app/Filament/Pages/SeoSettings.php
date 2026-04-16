<?php

namespace App\Filament\Pages;

use App\Models\PageSeoSetting;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class SeoSettings extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'SEO Settings';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'SEO Settings';
    protected string $view = 'filament.pages.seo-settings';

    public ?array $globalData = [];
    public ?array $pageData = [];

    public function mount(): void
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        $this->globalForm->fill([
            'meta_title_default' => $settings['meta_title_default'] ?? '',
            'meta_title_suffix' => $settings['meta_title_suffix'] ?? '',
            'meta_description_default' => $settings['meta_description_default'] ?? '',
            'google_analytics_id' => $settings['google_analytics_id'] ?? '',
            'google_tag_manager_id' => $settings['google_tag_manager_id'] ?? '',
            'facebook_pixel_id' => $settings['facebook_pixel_id'] ?? '',
            'posthog_api_key' => $settings['posthog_api_key'] ?? '',
            'posthog_host' => $settings['posthog_host'] ?? 'https://us.i.posthog.com',
            'robots_txt' => $settings['robots_txt'] ?? "User-agent: *\nAllow: /\n\nSitemap: " . url('/sitemap.xml'),
        ]);

        // Load per-page SEO data
        $this->loadPageSeoData();
    }

    protected function loadPageSeoData(): void
    {
        $defaultPages = PageSeoSetting::defaultPages();
        $existingPages = PageSeoSetting::all()->keyBy('page_slug');

        $pages = [];
        foreach ($defaultPages as $slug => $label) {
            $existing = $existingPages->get($slug);
            $pages[] = [
                'page_slug' => $slug,
                'page_label' => $label,
                'meta_title' => $existing?->meta_title ?? '',
                'meta_description' => $existing?->meta_description ?? '',
                'og_image_url' => $existing?->og_image_url ?? '',
            ];
        }

        $this->pageForm->fill([
            'pages' => $pages,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'globalForm',
            'pageForm',
        ];
    }

    public function globalForm(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Global SEO Defaults')
                    ->description('Fallback values used when a page has no custom SEO set.')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title_default')
                            ->label('Default Meta Title')
                            ->maxLength(70)
                            ->helperText('Fallback <title> tag. Max 60-70 characters recommended.')
                            ->placeholder('e.g., Anugraha Matrimony - Find Your Perfect Match'),

                        Forms\Components\TextInput::make('meta_title_suffix')
                            ->label('Meta Title Suffix')
                            ->maxLength(50)
                            ->helperText('Appended to all page titles. e.g., " | Anugraha Matrimony"')
                            ->placeholder('e.g., | Anugraha Matrimony'),

                        Forms\Components\Textarea::make('meta_description_default')
                            ->label('Default Meta Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Fallback meta description. Max 155-160 characters.')
                            ->placeholder('e.g., Find your perfect life partner with Anugraha Matrimony...'),
                    ]),

                \Filament\Schemas\Components\Section::make('Tracking & Analytics')
                    ->description('Add tracking codes for analytics and advertising.')
                    ->schema([
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('Google Analytics ID')
                            ->placeholder('G-XXXXXXXXXX or UA-XXXXXXX-X')
                            ->helperText('Google Analytics 4 measurement ID or Universal Analytics ID'),

                        Forms\Components\TextInput::make('google_tag_manager_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('GTM-XXXXXXX')
                            ->helperText('Container ID from Google Tag Manager'),

                        Forms\Components\TextInput::make('facebook_pixel_id')
                            ->label('Facebook Pixel ID')
                            ->placeholder('XXXXXXXXXXXXXXXX')
                            ->helperText('For Meta/Facebook ads tracking'),

                        Forms\Components\TextInput::make('posthog_api_key')
                            ->label('PostHog API Key')
                            ->placeholder('phc_XXXXXXXXXXXXXXXX')
                            ->helperText('Project API key from PostHog > Settings > Project API Key. Enables session recording + analytics.'),

                        Forms\Components\TextInput::make('posthog_host')
                            ->label('PostHog Host')
                            ->placeholder('https://us.i.posthog.com')
                            ->helperText('Default: https://us.i.posthog.com (US) or https://eu.i.posthog.com (EU)'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make('Robots.txt')
                    ->description('Control how search engines crawl your site.')
                    ->schema([
                        Forms\Components\Textarea::make('robots_txt')
                            ->label('Robots.txt Content')
                            ->rows(8)
                            ->helperText('This will be served at /robots.txt. Be careful with Disallow rules.'),
                    ]),
            ])
            ->statePath('globalData');
    }

    public function pageForm(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Per-Page SEO')
                    ->description('Override meta title and description for individual pages. Leave blank to use global defaults.')
                    ->schema([
                        Forms\Components\Repeater::make('pages')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('page_label')
                                    ->label('Page')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Hidden::make('page_slug'),

                                Forms\Components\TextInput::make('meta_title')
                                    ->label('Custom Meta Title')
                                    ->maxLength(70)
                                    ->placeholder('Leave blank for default'),

                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Custom Meta Description')
                                    ->rows(2)
                                    ->maxLength(160)
                                    ->placeholder('Leave blank for default'),

                                Forms\Components\TextInput::make('og_image_url')
                                    ->label('OG Image URL')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->helperText('Social sharing image for this page'),
                            ])
                            ->columns(1)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['page_label'] ?? null),
                    ]),
            ])
            ->statePath('pageData');
    }

    public function saveGlobal(): void
    {
        $data = $this->globalForm->getState();

        foreach ($data as $key => $value) {
            SiteSetting::setValue($key, $value ?? '');
        }

        Notification::make()
            ->title('Global SEO settings saved')
            ->success()
            ->send();
    }

    public function savePages(): void
    {
        $data = $this->pageForm->getState();

        foreach ($data['pages'] as $page) {
            $slug = $page['page_slug'];
            $label = $page['page_label'];
            $hasContent = !empty($page['meta_title']) || !empty($page['meta_description']) || !empty($page['og_image_url']);

            if ($hasContent) {
                PageSeoSetting::updateOrCreate(
                    ['page_slug' => $slug],
                    [
                        'page_label' => $label,
                        'meta_title' => $page['meta_title'] ?: null,
                        'meta_description' => $page['meta_description'] ?: null,
                        'og_image_url' => $page['og_image_url'] ?: null,
                    ]
                );
            } else {
                // Remove if all fields are empty (use global defaults)
                PageSeoSetting::where('page_slug', $slug)->delete();
            }
        }

        // Clear all page SEO cache
        PageSeoSetting::clearCache();

        Notification::make()
            ->title('Per-page SEO settings saved')
            ->success()
            ->send();
    }
}
