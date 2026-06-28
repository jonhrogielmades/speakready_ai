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
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => $user->id]);

        // Check if level is locked (Sequential Locking by Category)
        $status = 'locked';
        
        // Find the previous level in the same category
        $previousLevel = ArenaLevel::where('category_id', $level->category_id)
                                   ->where('level_number', '<', $level->level_number)
                                   ->orderBy('level_number', 'desc')
                                   ->first();
                                   
        if (!$previousLevel) {
            $status = 'active'; // First level in category is always active
        } else {
            $prevProgress = ArenaProgress::where('user_id', $user->id)
                ->where('arena_level_id', $previousLevel->id)
                ->first();
                
            if ($prevProgress && $prevProgress->best_score >= $previousLevel->required_score) {
                $status = 'active'; // Previous level passed, so this one is active
            }
        }
        
        // Explicit prerequisite overrides (if set)
        if ($level->prerequisite_level_id) {
            $prereqProgress = ArenaProgress::where('user_id', $user->id)
                ->where('arena_level_id', $level->prerequisite_level_id)
                ->first();
                
            $prereqLevel = ArenaLevel::find($level->prerequisite_level_id);
            if (!$prereqProgress || $prereqProgress->best_score < ($prereqLevel ? $prereqLevel->required_score : 80)) {
                $status = 'locked'; // Failed explicit prereq
            }
        }

        $progress = ArenaProgress::firstOrCreate(
            ['user_id' => $user->id, 'arena_level_id' => $level->id],
            ['status' => $status, 'best_score' => 0]
        );

        if ($progress->status === 'locked' && $status === 'locked') {
            return back()->with('error', 'This level is locked! Complete the prerequisite level with the required score to unlock it.');
        } else if ($status === 'active' && $progress->status === 'locked') {
            $progress->update(['status' => 'active']);
        }

        // Check Energy
        if ($profile->energy < $level->energy_cost) {
            // Auto-refill for seamless testing/gameplay
            $profile->energy = 5;
            $profile->save();
            session()->flash('success', 'You ran out of energy, so we gave you a free refill! Keep playing!');
        }

        // Consume Energy
        $profile->energy -= $level->energy_cost;
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

        // Create Interview Session specifically for Arena Mode
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $level->category_id ?? $defaultCategory->id,
            'difficulty' => $level->difficulty,
            'target_position' => $level->target_position,
            'num_questions' => 1, // Gamified Arena uses 1 question per level for rapid play
            'response_mode' => 'voice_and_text',
            'interview_focus' => $interviewFocus,
            'company_persona' => $level->ai_persona, // Inject persona
            'time_limit' => $level->time_limit_seconds ?? 0, // Inject time limit
            'status' => 'in_progress',
        ]);

        // Explicitly create the one Arena Question based on the mission text
        \App\Models\Question::create([
            'interview_session_id' => $session->id,
            'category_id' => $session->category_id,
            'question_text' => "Your mission: " . $level->mission_text . "\n\nPlease begin your response.",
            'difficulty' => $level->difficulty,
            'status' => 'active'
        ]);

        // Save arena context in session
        session(['arena_level_id' => $level->id]);
        session(['active_interview_id' => $session->id]);

        return redirect()->route('user.arena.match')->with('success', 'Arena Match Started! Good luck!');
    }

    public function arenaSession(Request $request)
    {
        $session_id = session('active_interview_id');
        $level_id = session('arena_level_id');
        
        if (!$session_id || !$level_id) {
            return redirect()->route('user.learning')->with('error', 'No active Arena Match found.');
        }

        $arenaLevel = ArenaLevel::find($level_id);
        $interviewSession = InterviewSession::with('category')->find($session_id);

        if (!$arenaLevel || !$interviewSession) {
            return redirect()->route('user.learning')->with('error', 'Arena Match data is missing.');
        }

        // Determine if mobile view
        $isMobile = false;
        $userAgent = $request->header('User-Agent');
        if (preg_match('/Mobile|Android|BlackBerry|IEMobile|Silk/i', $userAgent)) {
            $isMobile = true;
        }

        return view('user.arena-session', compact('arenaLevel', 'interviewSession', 'isMobile'));
    }
}
