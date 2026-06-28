<?php

namespace App\Http\Controllers;

use App\Models\ArenaLevel;
use App\Services\AIService;
use Illuminate\Http\Request;

class AdminArenaController extends Controller
{
    public function index()
    {
        $levels = ArenaLevel::with('progress')->orderBy('level_number', 'asc')->get();
        
        // Calculate analytics
        foreach ($levels as $level) {
            $totalAttempts = $level->progress->count();
            $passedAttempts = $level->progress->where('best_score', '>=', $level->required_score)->count();
            $level->pass_rate = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100) : 0;
            $level->avg_score = $totalAttempts > 0 ? round($level->progress->avg('best_score')) : 0;
        }

        $allLevels = ArenaLevel::orderBy('level_number', 'asc')->get(); // For prerequisite dropdown

        return view('admin.arena', compact('levels', 'allLevels'));
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules());
        $data = $request->all();
        $data['is_hidden'] = $request->has('is_hidden');

        ArenaLevel::create($data);

        return redirect()->route('admin.arena')->with('success', 'Arena Game created successfully.');
    }

    public function update(Request $request, ArenaLevel $arena_level)
    {
        $request->validate($this->validationRules($arena_level->id));
        $data = $request->all();
        $data['is_hidden'] = $request->has('is_hidden');

        $arena_level->update($data);

        return redirect()->route('admin.arena')->with('success', 'Arena Game updated successfully.');
    }

    public function destroy(ArenaLevel $arena_level)
    {
        $arena_level->delete();
        return redirect()->route('admin.arena')->with('success', 'Arena Game deleted successfully.');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'level_number' => 'required|integer|unique:arena_levels',
        ]);

        $gameData = AIService::generateArenaGame($request->topic);

        if (!$gameData) {
            return redirect()->route('admin.arena')->with('error', 'Failed to generate game with AI. Please try again.');
        }

        $gameData['level_number'] = $request->level_number;
        ArenaLevel::create($gameData);

        return redirect()->route('admin.arena')->with('success', 'Arena Game automatically generated and saved!');
    }

    private function validationRules($id = null)
    {
        $levelRule = $id ? 'required|integer|unique:arena_levels,level_number,' . $id : 'required|integer|unique:arena_levels';
        return [
            'level_number' => $levelRule,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'mission_text' => 'nullable|string',
            'target_position' => 'required|string|max:255',
            'difficulty' => 'required|string|max:255',
            'required_score' => 'required|integer|min:0|max:100',
            'xp_reward' => 'required|integer|min:0',
            'energy_cost' => 'required|integer|min:0',
            'ai_persona' => 'nullable|string|max:255',
            'ai_custom_prompt' => 'nullable|string',
            'time_limit_seconds' => 'nullable|integer|min:0',
            'banned_words' => 'nullable|string',
            'target_tone' => 'nullable|string|max:255',
            'custom_badge_name' => 'nullable|string|max:255',
            'skill_xp_type' => 'nullable|string|max:255',
            'skill_xp_amount' => 'nullable|integer|min:0',
            'prerequisite_level_id' => 'nullable|exists:arena_levels,id',
        ];
    }
}
