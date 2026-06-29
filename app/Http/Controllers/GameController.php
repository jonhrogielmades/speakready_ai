<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\InterviewSession;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function startLevel(Request $request, $id)
    {
        $level = GameLevel::findOrFail($id);
        $user = Auth::user();
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => $user->id]);

        // Check if level is locked (Sequential Locking by Category)
        $status = 'locked';
        
        // Find the previous level in the same category
        $previousLevel = GameLevel::where('category_id', $level->category_id)
                                   ->where('level_number', '<', $level->level_number)
                                   ->orderBy('level_number', 'desc')
                                   ->first();
                                   
        if (!$previousLevel) {
            $status = 'active'; // First level in category is always active
        } else {
            $prevProgress = GameProgress::where('user_id', $user->id)
                ->where('game_level_id', $previousLevel->id)
                ->first();
                
            if ($prevProgress && $prevProgress->best_score >= $previousLevel->required_score) {
                $status = 'active'; // Previous level passed, so this one is active
            }
        }
        
        // Explicit prerequisite overrides (if set)
        if ($level->prerequisite_level_id) {
            $prereqProgress = GameProgress::where('user_id', $user->id)
                ->where('game_level_id', $level->prerequisite_level_id)
                ->first();
                
            $prereqLevel = GameLevel::find($level->prerequisite_level_id);
            if (!$prereqProgress || $prereqProgress->best_score < ($prereqLevel ? $prereqLevel->required_score : 80)) {
                $status = 'locked'; // Failed explicit prereq
            }
        }

        $progress = GameProgress::firstOrCreate(
            ['user_id' => $user->id, 'game_level_id' => $level->id],
            ['status' => $status, 'best_score' => 0]
        );

        if ($progress->status === 'locked' && $status === 'locked') {
            return back()->with('error', 'This level is locked! Complete the prerequisite level with the required score to unlock it.');
        } else if ($status === 'active' && $progress->status === 'locked') {
            $progress->update(['status' => 'active']);
        }

        // Check Energy
        $energyCost = $level->energy_cost;
        if ($profile->hasPerk('energy_efficiency')) {
            $energyCost = max(0, $energyCost - 1);
        }

        if ($profile->energy < $energyCost) {
            // Auto-refill for seamless testing/gameplay
            $profile->energy = 5;
            $profile->save();
            session()->flash('success', 'You ran out of energy, so we gave you a free refill! Keep playing!');
        }

        // Consume Energy
        $profile->energy -= $energyCost;
        $profile->save();

        // Combine mission text and custom prompt
        $interviewFocus = $level->mission_text;
        if ($level->ai_custom_prompt) {
            $interviewFocus .= "\n\nCRITICAL HIDDEN AI INSTRUCTION: " . $level->ai_custom_prompt;
        }

        // Get or create a default category for Arena levels
        $defaultCategory = \App\Models\Category::firstOrCreate(
            ['title' => 'General Behavioral'],
            ['status' => 'active']
        );

        $questions = $level->parsed_questions;

        $timeLimit = $level->time_limit_seconds ?? 0;
        if ($timeLimit > 0 && $profile->hasPerk('time_extension')) {
            $timeLimit += 30;
        }

        // Create Interview Session specifically for Game Mode
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $level->category_id ?? $defaultCategory->id,
            'difficulty' => $level->difficulty,
            'target_position' => $level->target_position,
            'num_questions' => count($questions), // Dynamic based on challenge
            'response_mode' => 'hybrid',
            'interview_focus' => $interviewFocus,
            'company_persona' => $level->ai_persona, // Inject persona
            'time_limit' => $timeLimit, // Inject time limit
            'status' => 'in_progress',
        ]);

        foreach ($questions as $qText) {
            \App\Models\Question::create([
                'interview_session_id' => $session->id,
                'category_id' => $session->category_id,
                'question_text' => $qText,
                'difficulty' => $level->difficulty,
                'status' => 'active'
            ]);
        }

        // Save arena context in session
        session(['game_level_id' => $level->id]);
        session(['active_interview_id' => $session->id]);

        return redirect()->route('user.game.match')->with('success', 'Learning Game Started! Good luck!');
    }

    public function arenaSession(Request $request)
    {
        $session_id = session('active_interview_id');
        $level_id = session('game_level_id');
        
        if (!$session_id || !$level_id) {
            return redirect()->route('user.learning')->with('error', 'No active Learning Game found.');
        }

        $gameLevel = GameLevel::find($level_id);
        $interviewSession = InterviewSession::with('category')->find($session_id);

        if (!$gameLevel || !$interviewSession) {
            return redirect()->route('user.learning')->with('error', 'Learning Game data is missing.');
        }

        // Determine if mobile view
        $isMobile = false;
        $userAgent = $request->header('User-Agent');
        if (preg_match('/Mobile|Android|BlackBerry|IEMobile|Silk/i', $userAgent)) {
            $isMobile = true;
        }

        return view('user.game-session', compact('gameLevel', 'interviewSession', 'isMobile'));
    }
}
