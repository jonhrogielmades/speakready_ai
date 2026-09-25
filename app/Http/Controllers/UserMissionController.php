<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserMissionController extends Controller
{
    public function index()
    {
        $missions = $this->missionsForGoal();

        return $this->mobileView('user.missions', compact('missions'));
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal' => 'nullable|string|max:240',
        ]);

        return response()->json([
            'success' => true,
            'missions' => $this->missionsForGoal($validated['goal'] ?? null)->values(),
        ]);
    }

    private function missionsForGoal(?string $goal = null): Collection
    {
        $topic = trim((string) $goal);
        $focus = $topic !== '' ? $topic : 'your next Philippines interview';

        return collect([
            [
                'id' => 'direct-answer',
                'title' => 'Direct Answer Sprint',
                'category' => 'Job Interview',
                'difficulty' => 'Easy',
                'duration' => 45,
                'intent' => 'Confident',
                'icon' => 'fa-bullseye',
                'color' => '#2563eb',
                'prompt' => "Answer this clearly in under one minute: {$focus}. What should the interviewer remember first?",
                'success_criteria' => [
                    'Open with the answer in one sentence.',
                    'Add one true example or proof point.',
                    'Close with the job or program connection.',
                ],
                'coach_tip' => 'Start with the point before adding background.',
            ],
            [
                'id' => 'proof-story',
                'title' => 'Proof Story Drill',
                'category' => 'Behavioral',
                'difficulty' => 'Medium',
                'duration' => 75,
                'intent' => 'Accountable',
                'icon' => 'fa-list-check',
                'color' => '#16a34a',
                'prompt' => "Tell a true story connected to {$focus}. What happened, what did you do, and what changed?",
                'success_criteria' => [
                    'Name the situation briefly.',
                    'Say the action you personally took.',
                    'Include the result, lesson, or next step.',
                ],
                'coach_tip' => 'Spend most of the answer on your action and result.',
            ],
            [
                'id' => 'polite-problem',
                'title' => 'Polite Problem Solver',
                'category' => 'Customer Service',
                'difficulty' => 'Medium',
                'duration' => 60,
                'intent' => 'Calm',
                'icon' => 'fa-headset',
                'color' => '#0ea5e9',
                'prompt' => "Explain a customer or team problem related to {$focus}, acknowledge the concern, and propose the next action.",
                'success_criteria' => [
                    'Acknowledge the concern without blame.',
                    'State the practical next action.',
                    'Keep the tone calm and respectful.',
                ],
                'coach_tip' => 'Use steady language: acknowledge, explain, act.',
            ],
            [
                'id' => 'role-fit',
                'title' => 'Role-Fit Pitch',
                'category' => 'Role Fit',
                'difficulty' => 'Hard',
                'duration' => 60,
                'intent' => 'Persuasive',
                'icon' => 'fa-briefcase',
                'color' => '#f59e0b',
                'prompt' => "Why are you a strong fit for {$focus}? Connect the role need to one skill, one experience, and one contribution.",
                'success_criteria' => [
                    'Name the role need.',
                    'Connect one skill or experience.',
                    'State how you can contribute.',
                ],
                'coach_tip' => 'Match the job need instead of listing traits.',
            ],
        ]);
    }
}
