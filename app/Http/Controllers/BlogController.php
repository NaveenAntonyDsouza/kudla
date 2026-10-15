<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(BlogPost::enabled(), 404);

        $category = $request->query('category');
        if ($category !== null && ! in_array($category, BlogPost::CATEGORIES, true)) {
            return redirect()->route('blog.index', [], 301);
        }

        $posts = BlogPost::published()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $categories = BlogPost::published()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('blog.index', compact('posts', 'categories', 'category'));
    }

    public function show(string $slug)
    {
        abort_unless(BlogPost::enabled(), 404);

        $post = BlogPost::where('slug', $slug)->firstOrFail();

        // Drafts and scheduled posts: only staff who can edit content see them (preview)
        if (! $post->isLive() && ! \App\Support\Permissions::can('manage_content')) {
            abort(404);
        }

        $related = BlogPost::published()
            ->whereKeyNot($post->getKey())
            ->orderByRaw('category = ? desc', [(string) $post->category])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
