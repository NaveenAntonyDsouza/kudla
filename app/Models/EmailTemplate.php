<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'subject',
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
     * Render subject and body with variable substitution.
     */
    public function render(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body_html;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, (string) $value, $subject);
            $body = str_replace($placeholder, (string) $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
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
