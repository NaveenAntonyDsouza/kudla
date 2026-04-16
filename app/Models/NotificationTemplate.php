<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'title_template',
        'body_template',
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

    public static function findBySlug(string $slug): ?self
    {
        $result = Cache::remember("notification_template.{$slug}", 3600, function () use ($slug) {
            $template = static::where('slug', $slug)->where('is_active', true)->first();

            return $template ? $template->toArray() : null;
        });

        if (! $result) {
            return null;
        }

        return is_array($result) ? (new static)->forceFill($result) : $result;
    }

    public function render(array $data): array
    {
        $title = $this->title_template;
        $body = $this->body_template;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $title = str_replace($placeholder, (string) $value, $title);
            $body = str_replace($placeholder, (string) $value, $body);
        }

        return ['title' => $title, 'body' => $body];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $t) => Cache::forget("notification_template.{$t->slug}"));
        static::deleted(fn (self $t) => Cache::forget("notification_template.{$t->slug}"));
    }
}
