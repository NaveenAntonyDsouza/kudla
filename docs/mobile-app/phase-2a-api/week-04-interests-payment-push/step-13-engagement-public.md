# Step 13 — Contact Form + Static Pages + Success Stories

## Goal
- `POST /api/v1/contact` (public) — contact form
- `GET /api/v1/static-pages/{slug}` (public) — DB-backed static pages
- `GET /api/v1/success-stories` (public) — paginated feed
- `POST /api/v1/success-stories` (auth) — submit story

## Procedure

### 1. `ContactController`

```php
public function submit(Request $request): JsonResponse
{
    $data = $request->validate([
        'name' => 'required|string|max:120',
        'email' => 'required|email',
        'phone' => 'nullable|digits:10',
        'subject' => 'required|string|max:200',
        'message' => 'required|string|max:2000',
    ]);

    \App\Models\ContactSubmission::create($data);

    // Send email to admin
    \Mail::raw(
        "New contact inquiry:\n\n" . json_encode($data, JSON_PRETTY_PRINT),
        fn ($m) => $m->to(\App\Models\SiteSetting::getValue('support_email'))->subject('New contact: ' . $data['subject']),
    );

    return ApiResponse::created(['message' => 'Thanks! We\'ll reply within 24 hours.']);
}
```

### 2. `StaticPageController`

```php
public function show(string $slug): JsonResponse
{
    $page = \App\Models\StaticPage::where('slug', $slug)->where('is_active', true)->first();
    if (! $page) return ApiResponse::error('NOT_FOUND', 'Page not found.', status: 404);

    // Variable substitution (same as web)
    $siteName = \App\Models\SiteSetting::getValue('site_name');
    $supportEmail = \App\Models\SiteSetting::getValue('support_email');
    $content = str_replace(['{{ app_name }}', '{{ email }}'], [$siteName, $supportEmail], $page->content);

    return ApiResponse::ok([
        'slug' => $page->slug,
        'title' => $page->title,
        'content_html' => $content,
        'meta_description' => $page->meta_description,
        'updated_at' => $page->updated_at->toIso8601String(),
    ]);
}
```

### 3. `SuccessStoryController`

```php
public function index(Request $request): JsonResponse
{
    $paginator = \App\Models\SuccessStory::where('is_approved', true)
        ->latest('wedding_date')
        ->paginate(10);

    return ApiResponse::ok($paginator->items(), [
        'page' => $paginator->currentPage(),
        'per_page' => $paginator->perPage(),
        'total' => $paginator->total(),
        'last_page' => $paginator->lastPage(),
    ]);
}

public function store(Request $request): JsonResponse
{
    $data = $request->validate([
        'couple_names' => 'required|string|max:120',
        'story' => 'required|string|max:5000',
        'wedding_date' => 'nullable|date',
        'photo' => 'nullable|file|image|max:5120',
    ]);

    if ($request->hasFile('photo')) {
        $data['photo_url'] = $request->file('photo')->store('success-stories', 'public');
    }

    $story = \App\Models\SuccessStory::create(array_merge($data, [
        'user_id' => $request->user()->id,
        'is_approved' => false,
    ]));

    return ApiResponse::created([
        'story_id' => $story->id,
        'status' => 'pending',
        'message' => 'Thanks! We\'ll review and publish soon.',
    ]);
}
```

### 4. Routes

```php
// Public
Route::post('/contact', [\App\Http\Controllers\Api\V1\ContactController::class, 'submit'])->middleware('throttle:5,60');
Route::get('/static-pages/{slug}', [\App\Http\Controllers\Api\V1\StaticPageController::class, 'show']);
Route::get('/success-stories', [\App\Http\Controllers\Api\V1\SuccessStoryController::class, 'index']);

// Auth
Route::middleware('auth:sanctum')->post('/success-stories', [\App\Http\Controllers\Api\V1\SuccessStoryController::class, 'store']);
```

## Verification
- [ ] Contact form submits + emails admin
- [ ] Static pages render with variable substitution
- [ ] Success stories feed shows approved only

## Commit
```bash
git commit -am "phase-2a wk-04: step-13 contact + static pages + success stories"
```

## Next step
→ [step-14-onboarding-endpoints.md](step-14-onboarding-endpoints.md)
