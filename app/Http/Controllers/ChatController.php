<?php

namespace App\Http\Controllers;

use App\Support\Chats;

/**
 * Chats tab: accepted interests as conversations (the chat itself is still
 * the interest page, interests.show).
 */
class ChatController extends Controller
{
    public function index()
    {
        $profile = auth()->user()->profile;
        $chats = Chats::forProfile($profile);

        return view('chats.index', compact('profile', 'chats'));
    }
}
