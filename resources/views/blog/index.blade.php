@php
    $siteName = \App\Models\SiteSetting::getValue('site_name', 'Matrimony');
    $blogTitle = $category ? "{$category} – Blog" : 'Blog – Marriage Advice & Wedding Guides';
    $blogDescription = $category
        ? "Articles on " . mb_strtolower($category) . " from {$siteName}: practical advice for families and couples."
        : "Advice on finding a life partner, marriage preparation, weddings and family life, from {$siteName}.";
@endphp
<x-layouts.app :title="$blogTitle" :metaDescription="$blogDescription"
    :robots="$posts->total() === 0 ? 'noindex, follow' : null"
    :breadcrumbs="[['name' => 'Blog', 'url' => route('blog.index')]]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <h1 class="text-3xl font-serif font-bold text-gray-900">{{ $category ?: 'Blog' }}</h1>
        <p class="text-gray-600 mt-2">{{ $blogDescription }}</p>

        @if($categories->count() > 1)
            <div class="flex flex-wrap gap-2 mt-6">
                <a href="{{ route('blog.index') }}"
                   class="px-3 py-1 rounded-full text-sm border {{ $category ? 'border-gray-200 text-gray-700 bg-white' : 'border-transparent text-white bg-(--color-primary)' }}">All</a>
                @foreach($categories as $cat)
                    <a href="{{ route('blog.index', ['category' => $cat]) }}"
                       class="px-3 py-1 rounded-full text-sm border {{ $category === $cat ? 'border-transparent text-white bg-(--color-primary)' : 'border-gray-200 text-gray-700 bg-white' }}">{{ $cat }}</a>
                @endforeach
            </div>
        @endif

        @if($posts->total() === 0)
            <div class="text-center py-16">
                <p class="text-gray-600 font-medium">Articles coming soon</p>
                <p class="text-sm text-gray-500 mt-2">Advice on finding a life partner, weddings and family life.</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                @foreach($posts as $post)
                    <a href="{{ $post->url }}" class="block bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        @if($post->cover_url)
                            <img src="{{ $post->cover_url }}" alt="{{ $post->cover_alt ?: $post->title }}" loading="lazy" class="w-full aspect-video object-cover">
                        @endif
                        <div class="p-5">
                            @if($post->category)
                                <p class="text-xs uppercase tracking-wide font-medium text-(--color-primary)">{{ $post->category }}</p>
                            @endif
                            <h2 class="text-lg font-semibold text-gray-900 mt-2 leading-tight">{{ $post->title }}</h2>
                            <p class="text-sm text-gray-600 mt-2 line-clamp-3">{{ $post->summary }}</p>
                            <p class="text-xs text-gray-500 mt-3">{{ $post->published_at->format('j M Y') }} · {{ $post->reading_minutes }} min read</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </div>
</x-layouts.app>
