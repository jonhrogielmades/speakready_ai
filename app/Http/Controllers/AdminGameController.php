<?php

namespace App\Http\Controllers;

use App\Models\GameLevel;
use App\Models\Category;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminGameController extends Controller
{
    public function index()
    {
        $levels = GameLevel::with(['progress', 'category'])->orderBy('level_number', 'asc')->get();
        
        // Calculate analytics
        foreach ($levels as $level) {
            $totalAttempts = $level->progress->count();
            $passedAttempts = $level->progress->where('best_score', '>=', $level->required_score)->count();
            $level->pass_rate = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100) : 0;
            $level->avg_score = $totalAttempts > 0 ? round($level->progress->avg('best_score')) : 0;
        }

        $allLevels = GameLevel::orderBy('level_number', 'asc')->get(); // For prerequisite dropdown
        $categories = Category::where('status', 'active')->where('type', 'game')->get(); // For category dropdown

        return view('admin.game', compact('levels', 'allLevels', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate($this->validationRules(null, $request->category_id));
        $data = $request->all();
        $data['is_hidden'] = $request->has('is_hidden');

        GameLevel::create($data);

        return redirect()->route('admin.game')->with('success', 'Learning Game created successfully.');
    }

    public function update(Request $request, GameLevel $arena_level)
    {
        $request->validate($this->validationRules($arena_level->id, $request->category_id));
        $data = $request->all();
        $data['is_hidden'] = $request->has('is_hidden');

        $arena_level->update($data);

        return redirect()->route('admin.game')->with('success', 'Learning Game updated successfully.');
    }

    public function destroy(GameLevel $arena_level)
    {
        $arena_level->delete();
        return redirect()->route('admin.game')->with('success', 'Learning Game deleted successfully.');
    }

    public function generate(Request $request)
    {
        // Extend max execution time since generating 30 levels could take 1-2 minutes
        set_time_limit(300);

        $request->validate([
            'topic' => 'required|string|max:255',
            'level_number' => 'required|integer',
            'num_levels' => 'required|integer|min:1|max:30',
            'category_id' => ['required', Rule::exists('categories', 'id')->where('type', 'game')],
        ]);

        $startLevel = $request->level_number;
        $numLevels = $request->num_levels;
        $topic = $request->topic;
        
        $difficulties = ['beginner', 'intermediate', 'advanced'];
        $generatedCount = 0;

        for ($i = 0; $i < $numLevels; $i++) {
            $currentLevelNum = $startLevel + $i;
            
            // Skip if level number already exists in this category to avoid unique constraint violation
            if (GameLevel::where('level_number', $currentLevelNum)->where('category_id', $request->category_id)->exists()) {
                continue;
            }

            // Cycle through difficulties to ensure variation
            $difficulty = $difficulties[$i % 3];
            
            // Modify topic slightly to inform AI of difficulty
            $promptTopic = "{$topic}. Design this specifically for {$difficulty} difficulty level.";
            
            try {
                $gameData = AIService::generateGame($promptTopic);
            } catch (\Throwable $e) {
                Log::warning('Learning Game generation failed; using fallback content.', [
                    'topic' => $topic,
                    'level_number' => $currentLevelNum,
                    'error' => $e->getMessage(),
                ]);
                $gameData = null;
            }

            $gameData = $this->normalizeGeneratedGameData($gameData, $topic, $difficulty);

            if ($gameData) {
                $gameData['level_number'] = $currentLevelNum;
                $gameData['category_id'] = $request->category_id;
                $gameData['difficulty'] = $difficulty; // Force the difficulty
                
                // Adjust score based on difficulty
                if ($difficulty == 'beginner') $gameData['required_score'] = max(50, min(70, $gameData['required_score']));
                if ($difficulty == 'intermediate') $gameData['required_score'] = max(70, min(85, $gameData['required_score']));
                if ($difficulty == 'advanced') $gameData['required_score'] = max(85, min(100, $gameData['required_score']));

                GameLevel::create($gameData);
                $generatedCount++;
            }
        }

        if ($generatedCount === 0) {
            return redirect()->route('admin.game')->with('error', 'Failed to generate games. Maybe the level numbers already exist or the AI timed out.');
        }

        return redirect()->route('admin.game')->with('success', "Successfully generated {$generatedCount} Learning Game(s)!");
    }

    private function validationRules($id = null, $categoryId = null)
    {
        $uniqueRule = \Illuminate\Validation\Rule::unique('game_levels', 'level_number');
        if ($categoryId) {
            $uniqueRule->where('category_id', $categoryId);
        }
        if ($id) {
            $uniqueRule->ignore($id);
        }

        return [
            'level_number' => ['required', 'integer', $uniqueRule],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('type', 'game')],
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
            'prerequisite_level_id' => 'nullable|exists:game_levels,id',
        ];
    }

    private function normalizeGeneratedGameData(?array $gameData, string $topic, string $difficulty): array
    {
        $fallback = $this->fallbackGameData($topic, $difficulty);
        $gameData = is_array($gameData) ? array_merge($fallback, $gameData) : $fallback;

        $gameData['title'] = $this->cleanText($gameData['title'] ?? $fallback['title'], $fallback['title'], 255);
        $gameData['description'] = $this->cleanText($gameData['description'] ?? $fallback['description'], $fallback['description'], 1000);
        $gameData['mission_text'] = $this->cleanText($gameData['mission_text'] ?? $fallback['mission_text'], $fallback['mission_text'], 3000);
        $gameData['target_position'] = $this->cleanText($gameData['target_position'] ?? $fallback['target_position'], $fallback['target_position'], 255);
        $gameData['ai_persona'] = $this->cleanText($gameData['ai_persona'] ?? $fallback['ai_persona'], $fallback['ai_persona'], 255);
        $gameData['ai_custom_prompt'] = $this->cleanText($gameData['ai_custom_prompt'] ?? $fallback['ai_custom_prompt'], $fallback['ai_custom_prompt'], 3000);
        $gameData['banned_words'] = $this->cleanText($gameData['banned_words'] ?? $fallback['banned_words'], $fallback['banned_words'], 255);
        $gameData['target_tone'] = $this->cleanText($gameData['target_tone'] ?? $fallback['target_tone'], $fallback['target_tone'], 255);
        $gameData['custom_badge_name'] = $this->cleanText($gameData['custom_badge_name'] ?? $fallback['custom_badge_name'], $fallback['custom_badge_name'], 255);
        $gameData['skill_xp_type'] = $this->cleanText($gameData['skill_xp_type'] ?? $fallback['skill_xp_type'], $fallback['skill_xp_type'], 255);

        $gameData['required_score'] = max(0, min(100, (int) ($gameData['required_score'] ?? $fallback['required_score'])));
        $gameData['xp_reward'] = max(0, (int) ($gameData['xp_reward'] ?? $fallback['xp_reward']));
        $gameData['energy_cost'] = max(0, (int) ($gameData['energy_cost'] ?? $fallback['energy_cost']));
        $gameData['time_limit_seconds'] = isset($gameData['time_limit_seconds']) ? max(0, (int) $gameData['time_limit_seconds']) : null;
        $gameData['skill_xp_amount'] = max(0, (int) ($gameData['skill_xp_amount'] ?? $fallback['skill_xp_amount']));

        return $gameData;
    }

    private function fallbackGameData(string $topic, string $difficulty): array
    {
        $topic = $this->cleanText($topic, 'Communication Practice', 120);
        $titleDifficulty = ucfirst($difficulty);

        return [
            'title' => "{$titleDifficulty} {$topic} Challenge",
            'description' => "A structured practice level for improving {$topic} through focused interview-style prompts.",
            'mission_text' => "1. Describe your current challenge with {$topic}.\n2. Share one specific example where this skill mattered.\n3. Explain the action you took.\n4. Name the result or lesson learned.\n5. Describe how you will improve next time.",
            'target_position' => 'Better Communication',
            'difficulty' => $difficulty,
            'required_score' => $difficulty === 'advanced' ? 90 : ($difficulty === 'intermediate' ? 78 : 60),
            'xp_reward' => $difficulty === 'advanced' ? 750 : ($difficulty === 'intermediate' ? 600 : 450),
            'energy_cost' => $difficulty === 'advanced' ? 2 : 1,
            'ai_persona' => 'Supportive Interview Coach',
            'ai_custom_prompt' => 'Ask clear follow-up questions and evaluate structure, specificity, and confidence.',
            'time_limit_seconds' => 120,
            'banned_words' => 'um, like, basically',
            'target_tone' => 'Confident',
            'custom_badge_name' => "{$titleDifficulty} Communicator",
            'skill_xp_type' => 'Communication',
            'skill_xp_amount' => $difficulty === 'advanced' ? 75 : 50,
        ];
    }

    private function cleanText(?string $value, string $fallback, int $limit = 255): string
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', (string) $value));
        if ($cleaned === '') {
            $cleaned = $fallback;
        }

        return mb_substr($cleaned, 0, $limit);
    }
}
