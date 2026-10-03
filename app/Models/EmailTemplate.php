<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'subject',
        'preheader',
        'body_html',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Find template by slug with caching.
     */
    public static function findBySlug(string $slug): ?self
    {
        $result = Cache::remember("email_template.{$slug}", 3600, function () use ($slug) {
            $template = static::where('slug', $slug)->where('is_active', true)->first();

            return $template ? $template->toArray() : null;
        });

        if (! $result) {
            return null;
        }

        // Reconstruct from cached array to avoid __PHP_Incomplete_Class
        if (is_array($result)) {
            return (new static)->forceFill($result);
        }

        return $result;
    }

    /**
     * Whether an admin has switched this email off (Email Templates → Active).
     * Only an existing row marked inactive counts: a slug with no row at all
     * still sends, using the mailable's built-in fallback. Read uncached so a
     * switch-off takes effect immediately, and fails open (keeps sending) if
     * the table can't be read.
     */
    public static function isDisabled(string $slug): bool
    {
        try {
            return static::where('slug', $slug)->where('is_active', false)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Render subject, preview text and body with variable substitution.
     *
     * In the body every value is escaped unless its name ends in _HTML (markup
     * the mailable built and escaped itself). Members choose their own names,
     * so a value must never turn into a link or an image in someone's inbox.
     * Subject and preview text are plain text: escaped when output, not here.
     */
    public function render(array $data): array
    {
        $subject = (string) $this->subject;
        $preheader = (string) ($this->preheader ?? '');
        $body = (string) $this->body_html;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $text = str_replace(["\r", "\n"], ' ', (string) $value);
            $subject = str_replace($placeholder, $text, $subject);
            $preheader = str_replace($placeholder, $text, $preheader);
            $body = str_replace($placeholder, str_ends_with($key, '_HTML') ? (string) $value : e((string) $value), $body);
        }

        return [
            'subject' => $this->withoutPlaceholders($subject, 'subject'),
            'preheader' => $this->withoutPlaceholders($preheader, 'preheader'),
            'body' => $this->withoutPlaceholders($body, 'body'),
        ];
    }

    /**
     * A placeholder this email doesn't fill (an admin's typo, or a variable
     * from another template) is removed and logged, never shown to members
     * as "{{CODE}}".
     */
    private function withoutPlaceholders(string $text, string $part): string
    {
        $pattern = '/\{\{\s*([A-Z0-9_]+)\s*\}\}/';
        if (! preg_match_all($pattern, $text, $matches)) {
            return $text;
        }

        Log::warning('Email template has unfilled placeholders', [
            'slug' => $this->slug,
            'part' => $part,
            'placeholders' => array_values(array_unique($matches[1])),
        ]);

        return preg_replace($pattern, '', $text);
    }

    /**
     * Clear cache when template is saved/deleted.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $template) => Cache::forget("email_template.{$template->slug}"));
        static::deleted(fn (self $template) => Cache::forget("email_template.{$template->slug}"));
    }
}
