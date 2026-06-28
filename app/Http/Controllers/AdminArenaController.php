<?php

namespace App\Http\Controllers;

use App\Models\ArenaLevel;
use App\Models\Category;
use App\Services\AIService;
use Illuminate\Http\Request;

class AdminArenaController extends Controller
{
    public function index()
    {
        $levels = ArenaLevel::with(['progress', 'category'])->orderBy('level_number', 'asc')->get();
        
        // Calculate analytics
        foreach ($levels as $level) {
            $totalAttempts = $level->progress->count();
            $passedAttempts = $level->progress->where('best_score', '>=', $level->required_score)->count();
            $level->pass_rate = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100) : 0;
            $level->avg_score = $totalAttempts > 0 ? round($level->progress->avg('best_score')) : 0;
        }

        $allLevels = ArenaLevel::orderBy('level_number', 'asc')->get(); // For prerequisite dropdown
        $categories = Category::where('status', 'active')->get(); // For category dropdown

        return view('admin.arena', compact('levels', 'allLevels', 'categories'));
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
        // Extend max execution time since generating 30 levels could take 1-2 minutes
        set_time_limit(300);

        $request->validate([
            'topic' => 'required|string|max:255',
            'level_number' => 'required|integer',
            'num_levels' => 'required|integer|min:1|max:30',
            'category_id' => 'required|exists:categories,id',
        ]);

        $startLevel = $request->level_number;
        $numLevels = $request->num_levels;
        $topic = $request->topic;
        
        $difficulties = ['beginner', 'intermediate', 'advanced'];
        $generatedCount = 0;

        for ($i = 0; $i < $numLevels; $i++) {
            $currentLevelNum = $startLevel + $i;
            
            // Skip if level number already exists to avoid unique constraint violation
            if (ArenaLevel::where('level_number', $currentLevelNum)->exists()) {
                continue;
            }

            // Cycle through difficulties to ensure variation
            $difficulty = $difficulties[$i % 3];
            
            // Modify topic slightly to inform AI of difficulty
            $promptTopic = "{$topic}. Design this specifically for {$difficulty} difficulty level.";
            
            $gameData = AIService::generateArenaGame($promptTopic);

            if ($gameData) {
                $gameData['level_number'] = $currentLevelNum;
                $gameData['category_id'] = $request->category_id;
                $gameData['difficulty'] = $difficulty; // Force the difficulty
                
                // Adjust score based on difficulty
                if ($difficulty == 'beginner') $gameData['required_score'] = max(50, min(70, $gameData['required_score']));
                if ($difficulty == 'intermediate') $gameData['required_score'] = max(70, min(85, $gameData['required_score']));
                if ($difficulty == 'advanced') $gameData['required_score'] = max(85, min(100, $gameData['required_score']));

                ArenaLevel::create($gameData);
                $generatedCount++;
            }
        }

        if ($generatedCount === 0) {
            return redirect()->route('admin.arena')->with('error', 'Failed to generate games. Maybe the level numbers already exist or the AI timed out.');
        }

        return redirect()->route('admin.arena')->with('success', "Successfully generated {$generatedCount} Arena Game(s)!");
    }

    private function validationRules($id = null)
    {
        $levelRule = $id ? 'required|integer|unique:arena_levels,level_number,' . $id : 'required|integer|unique:arena_levels';
        return [
            'level_number' => $levelRule,
            'category_id' => 'required|exists:categories,id',
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
