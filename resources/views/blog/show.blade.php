@php
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $logoUrl = \App\Models\ThemeSetting::getTheme()->logo_url ?? null;
    $author = $post->author_name ?: "{$siteName} Team";
    $description = $post->meta_description ?: $post->summary;
    $isLive = $post->isLive();
@endphp
<x-layouts.app :title="$post->meta_title ?: $post->title" :metaDescription="$description"
    :canonicalUrl="$post->url" ogType="article" :ogImage="$post->cover_url"
    :robots="$isLive ? null : 'noindex, nofollow'"
    :breadcrumbs="array_values(array_filter([
        ['name' => 'Blog', 'url' => route('blog.index')],
        $post->category ? ['name' => $post->category, 'url' => route('blog.index', ['category' => $post->category])] : null,
        ['name' => $post->title, 'url' => $post->url],
    ]))"
    :jsonLd="array_filter([
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $description,
        'image' => $post->cover_url,
        'datePublished' => $post->published_at?->toAtomString(),
        'dateModified' => $post->updated_at?->toAtomString(),
        'author' => ['@type' => $post->author_name ? 'Person' : 'Organization', 'name' => $author],
        'publisher' => array_filter(['@type' => 'Organization', 'name' => $siteName, 'logo' => $logoUrl ? ['@type' => 'ImageObject', 'url' => $logoUrl] : null]),
        'mainEntityOfPage' => $post->url,
    ])">
    <style>
        .blog-body { color: #374151; font-size: 1.0625rem; line-height: 1.75; }
        .blog-body > * + * { margin-top: 1.1em; }
        .blog-body h2 { font-size: 1.5rem; line-height: 1.3; font-weight: 700; color: #111827; margin-top: 1.8em; }
        .blog-body h3 { font-size: 1.2rem; line-height: 1.35; font-weight: 600; color: #111827; margin-top: 1.5em; }
        .blog-body a { color: var(--color-primary); text-decoration: underline; }
        .blog-body ul { list-style: disc; padding-left: 1.5em; }
        .blog-body ol { list-style: decimal; padding-left: 1.5em; }
        .blog-body li + li { margin-top: .35em; }
        .blog-body blockquote { border-left: 4px solid var(--color-primary); padding-left: 1em; font-style: italic; color: #4b5563; }
        .blog-body img { max-width: 100%; height: auto; border-radius: .5rem; }
        .blog-body table { width: 100%; border-collapse: collapse; font-size: .95rem; }
        .blog-body th, .blog-body td { border: 1px solid #e5e7eb; padding: .5em .75em; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        .blog-body th { background: #f9fafb; font-weight: 600; }
        @media (max-width: 480px) {
            .blog-body table { font-size: .85rem; }
            .blog-body th, .blog-body td { padding: .45em .5em; }
        }
    </style>

    <article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @unless($isLive)
            <p class="mb-6 p-4 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-700">
                Preview: this article is <strong>{{ $post->status === 'published' ? 'scheduled for ' . $post->published_at?->format('j M Y, H:i') : 'a draft' }}</strong>. Only staff can see it.
            </p>
        @endunless

        <p class="text-sm text-gray-500">
            <a href="{{ route('blog.index') }}" class="hover:underline">Blog</a>
            @if($post->category)
                <span class="mx-1">/</span>
                <a href="{{ route('blog.index', ['category' => $post->category]) }}" class="hover:underline">{{ $post->category }}</a>
            @endif
        </p>

        <h1 class="text-3xl md:text-4xl font-serif font-bold text-gray-900 mt-3 leading-tight">{{ $post->title }}</h1>
        <p class="text-sm text-gray-500 mt-3">
            {{ $author }}
            @if($post->published_at) · {{ $post->published_at->format('j M Y') }} @endif
            · {{ $post->reading_minutes }} min read
        </p>

        @if($post->cover_url)
            <img src="{{ $post->cover_url }}" alt="{{ $post->cover_alt ?: $post->title }}" class="w-full aspect-video object-cover rounded-xl mt-8">
        @endif

        <div class="blog-body mt-8">
            {!! $post->safe_body !!}
        </div>

        @if($post->related_url)
            <p class="mt-8">
                <a href="{{ $post->related_url }}" class="font-medium text-(--color-primary) hover:underline">{{ $post->related_label ?: 'Read more' }} →</a>
            </p>
        @endif

        <div class="mt-12 p-6 rounded-xl border border-gray-200 bg-gray-50">
            @guest
                <p class="text-lg font-semibold text-gray-900">Looking for a life partner?</p>
                <p class="text-sm text-gray-600 mt-2">Create your free profile on {{ $siteName }} and meet families who share your faith and values.</p>
                <a href="{{ route('register') }}" class="inline-block mt-4 px-5 py-2 rounded-lg bg-(--color-primary) text-white text-sm font-semibold">Register free</a>
            @else
                <p class="text-lg font-semibold text-gray-900">Ready to look for your match?</p>
                <a href="{{ route('discover.hub') }}" class="inline-block mt-4 px-5 py-2 rounded-lg bg-(--color-primary) text-white text-sm font-semibold">Browse profiles</a>
            @endguest
        </div>

        @if($related->count())
            <h2 class="text-xl font-semibold text-gray-900 mt-12">More to read</h2>
            <div class="grid sm:grid-cols-2 gap-6 mt-4">
                @foreach($related as $item)
                    <a href="{{ $item->url }}" class="block p-5 bg-white rounded-xl border border-gray-200 hover:shadow-md transition-shadow">
                        @if($item->category)
                            <p class="text-xs uppercase tracking-wide font-medium text-(--color-primary)">{{ $item->category }}</p>
                        @endif
                        <p class="font-semibold text-gray-900 mt-1 leading-tight">{{ $item->title }}</p>
                        <p class="text-sm text-gray-600 mt-2 line-clamp-3">{{ $item->summary }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </article>
</x-layouts.app>
