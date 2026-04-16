<x-layouts.app title="Success Stories">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-serif font-bold text-gray-900">Success Stories</h1>
            <p class="mt-2 text-gray-500 max-w-2xl mx-auto">Real couples who found their perfect match through {{ \App\Models\SiteSetting::getValue('site_name', 'our platform') }}.</p>
        </div>

        @if(session('success'))
            <div class="max-w-2xl mx-auto mb-8 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if($stories->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($stories as $story)
                    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden hover:shadow-md transition-shadow">
                        @if($story->photo_url)
                            <div class="aspect-[4/3] bg-gray-100 overflow-hidden">
                                <img src="{{ Storage::disk('public')->url($story->photo_url) }}" alt="{{ $story->couple_names }}"
                                    class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-gradient-to-br from-(--color-primary-light) to-(--color-secondary-light) flex items-center justify-center">
                                <svg class="w-16 h-16 text-(--color-primary)/30" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="text-base font-semibold text-gray-900">{{ $story->couple_names }}</h3>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                @if($story->location)
                                    <span>{{ $story->location }}</span>
                                @endif
                                @if($story->wedding_date)
                                    <span>{{ $story->wedding_date->format('M Y') }}</span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm text-gray-600 line-clamp-4">{{ $story->story }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">{{ $stories->links() }}</div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <p class="text-gray-500">No success stories yet. Be the first to share yours!</p>
            </div>
        @endif

        {{-- Submit CTA --}}
        @auth
            <div class="mt-12 text-center">
                <div class="inline-block bg-(--color-primary-light) rounded-xl p-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Found your match through us?</h3>
                    <p class="text-sm text-gray-600 mb-4">Share your story and inspire others on their journey.</p>
                    <a href="{{ route('success-stories.create') }}" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Share Your Story
                    </a>
                </div>
            </div>
        @endauth
    </div>
</x-layouts.app>
