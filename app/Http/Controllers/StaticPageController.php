<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;

class StaticPageController extends Controller
{
    public function show(string $slug)
    {
        // region pages live at /{slug} (one address for Google)
        if (StaticPage::isLanding($slug)) {
            return redirect('/' . $slug, 301);
        }

        $page = StaticPage::getBySlug($slug);

        if (!$page) {
            abort(404);
        }

        return view('pages.static', compact('page'));
    }
}
