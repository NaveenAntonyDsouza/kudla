<x-layouts.app title="Photo Requests">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Photo Requests</span>
        </p>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('info'))
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-700 font-medium">{{ session('info') }}</p>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="flex items-center gap-6 border-b border-gray-200 mb-6">
            <a href="{{ route('photo-requests.index', ['tab' => 'received']) }}"
                class="pb-3 text-sm font-{{ $tab === 'received' ? 'semibold' : 'medium' }} border-b-2 {{ $tab === 'received' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Received @if($receivedCount > 0) <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold bg-red-500 text-white rounded-full">{{ $receivedCount }}</span> @endif
            </a>
            <a href="{{ route('photo-requests.index', ['tab' => 'sent']) }}"
                class="pb-3 text-sm font-{{ $tab === 'sent' ? 'semibold' : 'medium' }} border-b-2 {{ $tab === 'sent' ? 'border-(--color-primary) text-(--color-primary)' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Sent
            </a>
        </div>

        {{-- Received Tab --}}
        @if($tab === 'received')
            @if($received->count() > 0)
                <div class="space-y-3">
                    @foreach($received as $req)
                        @php $p = $req->requesterProfile; @endphp
                        <div class="bg-white rounded-lg border border-gray-200 p-4 flex items-center gap-4">
                            <a href="{{ route('profile.view', $p) }}" class="shrink-0">
                                <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-100">
                                    @if($p->primaryPhoto)
                                        <img src="{{ $p->primaryPhoto->full_url }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('profile.view', $p) }}" class="text-sm font-semibold text-(--color-primary) hover:underline">{{ $p->matri_id }}</a>
                                <p class="text-xs text-gray-600 truncate">{{ $p->full_name }} — {{ $p->religiousInfo?->religion }} {{ $p->religiousInfo?->denomination ?? $p->religiousInfo?->caste }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">Requested {{ $req->created_at->format('d M Y') }}</p>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                @if($req->status === 'pending')
                                    <form method="POST" action="{{ route('photo-requests.approve', $req) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('photo-requests.ignore', $req) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors">Ignore</button>
                                    </form>
                                @else
                                    <span class="px-2 py-1 text-[10px] font-medium rounded-full {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $received->appends(['tab' => 'received'])->links() }}</div>
            @else
                <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25a2.25 2.25 0 00-2.25-2.25H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                    <p class="text-gray-600 font-medium">No photo requests received yet</p>
                </div>
            @endif
        @endif

        {{-- Sent Tab --}}
        @if($tab === 'sent')
            @if($sent->count() > 0)
                <div class="space-y-3">
                    @foreach($sent as $req)
                        @php $p = $req->targetProfile; @endphp
                        <div class="bg-white rounded-lg border border-gray-200 p-4 flex items-center gap-4">
                            <a href="{{ route('profile.view', $p) }}" class="shrink-0">
                                <div class="w-14 h-14 rounded-full overflow-hidden bg-gray-100">
                                    @if($p->primaryPhoto)
                                        <img src="{{ $p->primaryPhoto->full_url }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('profile.view', $p) }}" class="text-sm font-semibold text-(--color-primary) hover:underline">{{ $p->matri_id }}</a>
                                <p class="text-xs text-gray-600 truncate">{{ $p->full_name }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">Sent {{ $req->created_at->format('d M Y') }}</p>
                            </div>
                            <span class="px-2 py-1 text-[10px] font-medium rounded-full
                                {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $req->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $req->status === 'ignored' ? 'bg-gray-100 text-gray-500' : '' }}">
                                {{ $req->status === 'pending' ? 'Awaiting' : ucfirst($req->status) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6">{{ $sent->appends(['tab' => 'sent'])->links() }}</div>
            @else
                <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                    <p class="text-gray-600 font-medium">You haven't sent any photo requests yet</p>
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
