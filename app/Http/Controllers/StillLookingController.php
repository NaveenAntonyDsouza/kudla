<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\User;
use App\Services\DormantProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Answers to the "Are you still looking?" email. The signed link shows the
 * chosen answer with a confirm button (mail scanners open links, so GET
 * changes nothing); the button POSTs to the same URL.
 *
 *  looking → counts as a visit, so no hiding; a hidden-for-inactivity profile comes back
 *  found   → profile hidden (reason found_match) + invitation to share the story
 *  hide    → profile hidden (as if the member hid it in Settings)
 */
class StillLookingController extends Controller
{
    public const ANSWERS = ['looking', 'found', 'hide'];

    public function show(User $user, Request $request)
    {
        $answer = $this->answer($request);

        return view('still-looking.page', ['answer' => $answer, 'done' => false, 'action' => $request->fullUrl(), 'name' => $this->firstName($user)]);
    }

    public function store(User $user, Request $request)
    {
        $answer = $this->answer($request);

        match ($answer) {
            'looking' => $this->stillLooking($user),
            'found' => $this->hideProfile($user, 'found_match'),
            'hide' => $this->hideProfile($user, null),
        };

        \App\Support\Analytics::track('still_looking_answered', ['answer' => $answer]);

        try {
            AdminActivityLog::create([
                'action' => 'still_looking_answered',
                'model_type' => User::class,
                'model_id' => $user->id,
                'changes' => ['answer' => $answer, 'matri_id' => $user->profile?->matri_id],
            ]);
        } catch (\Throwable) {
        }

        return view('still-looking.page', ['answer' => $answer, 'done' => true, 'action' => null, 'name' => $this->firstName($user)]);
    }

    private function stillLooking(User $user): void
    {
        DB::table('users')->where('id', $user->id)->update(['last_login_at' => now(), 'reengagement_level' => 0]);
        DormantProfiles::restore($user->id);
    }

    /** $reason null = hidden as by the member's own Settings switch. */
    private function hideProfile(User $user, ?string $reason): void
    {
        DB::table('profiles')->where('user_id', $user->id)
            ->update(['is_hidden' => true, 'hidden_reason' => $reason, 'updated_at' => now()]);
    }

    private function answer(Request $request): string
    {
        $answer = (string) $request->query('answer');
        abort_unless(in_array($answer, self::ANSWERS, true), 404);

        return $answer;
    }

    private function firstName(User $user): string
    {
        return trim(strtok((string) $user->name, ' ')) ?: '';
    }
}
