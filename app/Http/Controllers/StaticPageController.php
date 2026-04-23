<?php

namespace App\Http\Controllers;

use App\Models\StaticPage;

class StaticPageController extends Controller
{
    public function show(string $slug)
    {
        $page = StaticPage::getBySlug($slug);

        if (!$page) {
            abort(404);
        }

        return view('pages.static', compact('page'));
    }
}
