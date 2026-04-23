<x-layouts.app :title="$page->meta_title ?: $page->title">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">{{ $page->title }}</h1>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            {!! $page->rendered_content !!}
        </div>
    </div>
</x-layouts.app>
