<?php

use App\Filament\Resources\BlogPostResource;
use App\Http\Controllers\DiscoverController;
use App\Models\BlogPost;
use App\Models\SiteSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
| Blog (Oct 2026): built for all six sites, articles written first for
| catholicmatrimony.in. The footer link and sitemap entry only appear once a
| site has a published article, so the other sites show no empty blog.
*/

beforeEach(function () {
    Schema::dropIfExists('blog_posts');
    (require database_path('migrations/2026_10_15_000001_create_blog_posts_table.php'))->up();
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Schema::dropIfExists('static_pages');
    Schema::create('static_pages', function (Blueprint $t) {
        $t->id();
        $t->string('slug')->unique();
        $t->string('title');
        $t->boolean('is_active')->default(true);
        $t->integer('sort_order')->default(0);
        $t->timestamps();
    });
    Cache::flush();
});

afterEach(function () {
    Schema::dropIfExists('blog_posts');
    Schema::dropIfExists('site_settings');
    Schema::dropIfExists('static_pages');
});

function blogPost(array $attributes = []): BlogPost
{
    static $n = 0;
    $n++;

    return BlogPost::create($attributes + [
        'title' => "Article {$n}",
        'slug' => "article-{$n}",
        'body' => '<p>Text</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);
}

it('counts only published articles whose date has come', function () {
    blogPost(['slug' => 'live']);
    blogPost(['slug' => 'draft', 'status' => 'draft']);
    blogPost(['slug' => 'scheduled', 'published_at' => now()->addWeek()]);

    expect(BlogPost::published()->pluck('slug')->all())->toBe(['live'])
        ->and(BlogPost::where('slug', 'scheduled')->first()->isLive())->toBeFalse();
});

it('shows the footer link only once a post is published, and never when the blog is off', function () {
    expect(BlogPost::hasPublishedPosts())->toBeFalse();

    blogPost();
    expect(BlogPost::hasPublishedPosts())->toBeTrue();

    SiteSetting::setValue('blog_enabled', '0');
    expect(BlogPost::hasPublishedPosts())->toBeFalse();
});

it('hides drafts and scheduled posts from visitors and switches off with the setting', function () {
    blogPost(['slug' => 'draft-one', 'status' => 'draft']);
    blogPost(['slug' => 'later', 'published_at' => now()->addDay()]);

    $this->get('/blog/draft-one')->assertNotFound();
    $this->get('/blog/later')->assertNotFound();
    $this->get('/blog/no-such-post')->assertNotFound();

    SiteSetting::setValue('blog_enabled', '0');
    $this->get('/blog')->assertNotFound();
});

it('lists published posts in the sitemap', function () {
    blogPost(['slug' => 'catholic-wedding-documents']);
    blogPost(['slug' => 'not-yet', 'status' => 'draft']);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)->toContain(route('blog.index') . '</loc>')
        ->toContain(route('blog.show', 'catholic-wedding-documents') . '</loc>')
        ->toContain('<lastmod>')
        ->not->toContain('not-yet');
});

it('strips scripts and event handlers from article HTML', function () {
    $post = new BlogPost(['body' => '<h2>Banns</h2><p onclick="steal()">Read <a href="javascript:alert(1)">this</a></p><script>alert(1)</script><a href="/discover">ok</a>']);

    expect($post->safe_body)->toContain('<h2>Banns</h2>')
        ->toContain('href="/discover"')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:');
});

it('publishes "now" when no date is given', function () {
    expect(BlogPostResource::withPublishDate(['status' => 'published'])['published_at'])->not->toBeNull()
        ->and(BlogPostResource::withPublishDate(['status' => 'draft']))->not->toHaveKey('published_at');
});

it('lists Discover communities with members only, biggest first', function () {
    Cache::put('discover.counts.community-matrimony', ['bunts' => 40, 'billava' => 158, 'kulal' => 7], 60);

    $subs = [
        ['label' => 'Billava Profiles', 'slug' => 'billava', 'filter' => []],
        ['label' => 'Bunts Profiles', 'slug' => 'bunts', 'filter' => []],
        ['label' => 'Jogi Profiles', 'slug' => 'jogi', 'filter' => []],      // no members
        ['label' => 'Kulal Profiles', 'slug' => 'kulal', 'filter' => []],
    ];

    $listed = DiscoverController::withMembers('community-matrimony', $subs);

    expect(array_column($listed, 'slug'))->toBe(['billava', 'bunts', 'kulal'])
        ->and(array_column($listed, 'count'))->toBe([158, 40, 7]);
});
