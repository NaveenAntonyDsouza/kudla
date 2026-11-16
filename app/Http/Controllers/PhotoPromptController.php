<?php

namespace App\Http\Controllers;

use App\Support\AdminModules;
use App\Support\PhotoPrompt;
use Illuminate\Http\Response;

class PhotoPromptController extends Controller
{
    /** The × on the "Add your photo" bar: hide it for 3 days. */
    public function closeBar(): Response
    {
        if (AdminModules::photoBarMore() && auth()->user()) {
            PhotoPrompt::closeBar(auth()->user());
        }

        return response()->noContent();
    }
}
