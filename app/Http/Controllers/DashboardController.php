<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;

        return view('dashboard.index', compact('profile'));
    }
}
