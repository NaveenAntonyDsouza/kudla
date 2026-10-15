<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A blog article (Oct 2026; on every site, Site Settings → Blog switches it
 * off). Published = status "published" and a publish time that has arrived,
 * so posts can be scheduled.
 */
class BlogPost extends Model
{
    public const CATEGORIES = [
        'Finding a Partner',
        'Marriage Preparation',
        'Church Wedding',
        'Wedding Traditions',
        'Faith & Family',
        'Married Life',
        'Site News',
    ];

    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'body',
        'cover_image',
        'cover_alt',
        'author_name',
        'related_url',
        'related_label',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('blog.has-posts');
        static::saved($forget);
        static::deleted($forget);
    }

    /** On for every site unless switched off in Site Settings. */
    public static function enabled(): bool
    {
        return SiteSetting::getValue('blog_enabled', '1') === '1';
    }

    /** Footer link and sitemap only once there is something to read. */
    public static function hasPublishedPosts(): bool
    {
        if (! static::enabled()) {
            return false;
        }

        try {
            return (bool) \Illuminate\Support\Facades\Cache::remember('blog.has-posts', 600, fn () => static::published()->exists() ? 1 : 0);
        } catch (\Throwable $e) {
            return false; // table not migrated yet
        }
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->published_at !== null && $this->published_at->lte(now());
    }

    public function getUrlAttribute(): string
    {
        return route('blog.show', $this->slug);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return Str::startsWith($this->cover_image, ['http://', 'https://'])
            ? $this->cover_image
            : asset('storage/' . $this->cover_image);
    }

    /** The excerpt, or the opening of the article when none was written. */
    public function getSummaryAttribute(): string
    {
        $text = trim((string) $this->excerpt) !== '' ? $this->excerpt : $this->body;

        // Tags become spaces, so "<p>a</p><h2>b</h2>" reads "a b", not "ab"
        $plain = html_entity_decode(strip_tags(preg_replace('/<[^>]+>/', ' $0', (string) $text)), ENT_QUOTES);

        return Str::limit(trim(preg_replace('/\s+/', ' ', $plain)), 155);
    }

    public function getReadingMinutesAttribute(): int
    {
        return max(1, (int) round(str_word_count(strip_tags((string) $this->body)) / 200));
    }

    /**
     * Article HTML for display. Admin-written, but still cleaned (Symfony's
     * sanitizer, which Filament already ships): no scripts, styles, iframes or
     * on* attributes, whatever gets pasted into the editor.
     */
    public function getSafeBodyAttribute(): string
    {
        $config = (new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https', 'http'])
            ->allowRelativeMedias()
            ->withMaxInputLength(500000);

        $html = (new \Symfony\Component\HtmlSanitizer\HtmlSanitizer($config))->sanitize((string) $this->body);

        return static::unlinkUnpublished($html);
    }

    /**
     * Links to other articles that are not live yet (drafts, or scheduled for
     * a later week) become plain text, so a reader never lands on a 404; they
     * turn into links by themselves once that article is published.
     */
    public static function unlinkUnpublished(string $html): string
    {
        $host = preg_quote(rtrim(config('app.url'), '/'), '#');
        $pattern = '#<a\b[^>]*\bhref="(?:' . $host . ')?/blog/([a-z0-9-]+)/?"[^>]*>(.*?)</a>#is';

        if (! preg_match_all($pattern, $html, $matches)) {
            return $html;
        }

        $live = static::published()->whereIn('slug', array_unique($matches[1]))->pluck('slug')->all();

        return preg_replace_callback($pattern, fn ($m) => in_array($m[1], $live, true) ? $m[0] : $m[2], $html);
    }
}
