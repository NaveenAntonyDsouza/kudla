@php
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $url = url('/' . $page->slug);
    $description = $page->meta_description ?: null;
@endphp
<x-layouts.app :title="$page->meta_title ?: $page->title" :metaDescription="$description" :canonicalUrl="$url"
    :breadcrumbs="[['name' => $page->title, 'url' => $url]]"
    :jsonLd="array_filter([
        '@type' => 'WebPage',
        'name' => $page->title,
        'description' => $description,
        'url' => $url,
        'isPartOf' => ['@type' => 'WebSite', 'name' => $siteName, 'url' => url('/')],
    ])">
    <style>
        .lp-body { color: #374151; font-size: 1.0625rem; line-height: 1.75; }
        .lp-body > * + * { margin-top: 1.1em; }
        .lp-body h2 { font-size: 1.45rem; line-height: 1.3; font-weight: 700; color: #111827; margin-top: 1.8em; }
        .lp-body h3 { font-size: 1.15rem; line-height: 1.35; font-weight: 600; color: #111827; margin-top: 1.4em; }
        .lp-body a { color: var(--color-primary); text-decoration: underline; }
        .lp-body ul { list-style: disc; padding-left: 1.5em; }
        .lp-body ol { list-style: decimal; padding-left: 1.5em; }
        .lp-body li + li { margin-top: .35em; }
        .lp-cta { margin-top: 2.5rem; padding: 1.5rem; border-radius: .75rem; border: 1px solid #e5e7eb; background: #f9fafb; }
        .lp-cta a.lp-btn { display: inline-block; margin-top: 1rem; padding: .55rem 1.25rem; border-radius: .5rem; background: var(--color-primary); color: #fff; font-size: .875rem; font-weight: 600; text-decoration: none; }
        .lp-links { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .lp-links a { padding: .4rem .8rem; border: 1px solid #e5e7eb; border-radius: 999px; font-size: .875rem; color: #374151; text-decoration: none; background: #fff; }
        .lp-links a:hover { border-color: var(--color-primary); color: var(--color-primary); }
        .lp-articles { display: grid; gap: 1rem; margin-top: 1rem; }
        @media (min-width: 640px) { .lp-articles { grid-template-columns: repeat(3, 1fr); } }
        .lp-articles a { display: block; padding: 1rem; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; text-decoration: none; }
        .lp-articles a:hover { box-shadow: 0 6px 16px -10px rgba(0,0,0,.25); }
    </style>

    <article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <p class="text-sm text-gray-500"><a href="/" class="hover:underline">Home</a> <span class="mx-1">/</span> {{ $page->title }}</p>
        <h1 class="text-3xl md:text-4xl font-serif font-bold text-gray-900 mt-3 leading-tight">{{ $page->title }}</h1>

        <div class="lp-body mt-6">
            {!! $page->rendered_content !!}
        </div>

        <div class="lp-cta">
            @guest
                <p style="font-size: 1.125rem; font-weight: 600; color: #111827;">Looking for a Catholic life partner?</p>
                <p style="margin-top: .5rem; font-size: .9rem; color: #4b5563;">Create your free profile on {{ $siteName }}. Your phone number is shared only when you both accept an interest.</p>
                <a href="{{ route('register') }}" class="lp-btn">Register free</a>
            @else
                <p style="font-size: 1.125rem; font-weight: 600; color: #111827;">Ready to look for your match?</p>
                <a href="{{ route('search.quick') }}" class="lp-btn">Search profiles</a>
            @endguest
        </div>

        @if($others->isNotEmpty())
            <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin-top: 3rem;">Other communities</h2>
            <div class="lp-links">
                @foreach($others as $other)
                    <a href="/{{ $other['slug'] }}">{{ $other['title'] }}</a>
                @endforeach
            </div>
        @endif

        @if($articles->isNotEmpty())
            <h2 style="font-size: 1.25rem; font-weight: 600; color: #111827; margin-top: 3rem;">Read before the wedding</h2>
            <div class="lp-articles">
                @foreach($articles as $article)
                    <a href="{{ $article->url }}">
                        <p style="font-weight: 600; color: #111827; line-height: 1.3;">{{ $article->title }}</p>
                        @if($article->summary)<p style="margin-top: .5rem; font-size: .85rem; color: #4b5563;">{{ \Illuminate\Support\Str::limit($article->summary, 110) }}</p>@endif
                    </a>
                @endforeach
            </div>
        @endif
    </article>
</x-layouts.app>
