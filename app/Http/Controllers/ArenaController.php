<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArenaLevel;
use App\Models\ArenaProgress;
use App\Models\InterviewSession;
use Illuminate\Support\Facades\Auth;

class ArenaController extends Controller
{
    public function startLevel(Request $request, $id)
    {
        $level = ArenaLevel::findOrFail($id);
        $user = Auth::user();
        $profile = $user->profile;

        // Check if level is locked
        $progress = ArenaProgress::firstOrCreate(
            ['user_id' => $user->id, 'arena_level_id' => $level->id],
            ['status' => $level->level_number === 1 ? 'active' : 'locked', 'best_score' => 0]
        );

        if ($progress->status === 'locked') {
            return back()->with('error', 'This level is locked! Complete previous levels with an 80% score to unlock it.');
        }

        // Check Energy
        if ($profile->energy < $level->energy_cost) {
            return back()->with('error', 'Not enough Energy! You need at least ' . $level->energy_cost . ' lives to attempt this level.');
        }

        // Consume Energy
        $profile->energy -= $level->energy_cost;
        $profile->save();

        // Create Interview Session specifically for Arena Mode
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => 1, // Defaulting to 1 for General Behavioral
            'difficulty' => $level->difficulty,
            'target_position' => $level->target_position,
            'num_questions' => 1, // Gamified Arena uses 1 question per level for rapid play
            'response_mode' => 'voice_and_text',
            'interview_focus' => $level->mission_text,
            'status' => 'in_progress',
            // Store the arena level ID in JSON settings or a new column if we added one. 
            // For now we can use target_position or resume_text to store the arena flag temporarily,
            // but let's use the DB properly:
        ]);

        // Save arena context in session
        session(['arena_level_id' => $level->id]);
        session(['active_interview_id' => $session->id]);

        return redirect()->route('interview.session')->with('success', 'Arena Match Started! Good luck!');
    }
}
