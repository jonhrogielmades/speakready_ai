<?php

namespace App\Http\Controllers;

use App\Models\ArenaLevel;
use Illuminate\Http\Request;

class AdminArenaController extends Controller
{
    public function index()
    {
        $levels = ArenaLevel::orderBy('level_number', 'asc')->get();
        return view('admin.arena', compact('levels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'level_number' => 'required|integer|unique:arena_levels',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'mission_text' => 'nullable|string',
            'target_position' => 'required|string|max:255',
            'difficulty' => 'required|string|max:255',
            'required_score' => 'required|integer|min:0|max:100',
            'xp_reward' => 'required|integer|min:0',
            'energy_cost' => 'required|integer|min:0',
        ]);

        ArenaLevel::create($request->all());

        return redirect()->route('admin.arena')->with('success', 'Arena Game created successfully.');
    }

    public function update(Request $request, ArenaLevel $arena_level)
    {
        $request->validate([
            'level_number' => 'required|integer|unique:arena_levels,level_number,' . $arena_level->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'mission_text' => 'nullable|string',
            'target_position' => 'required|string|max:255',
            'difficulty' => 'required|string|max:255',
            'required_score' => 'required|integer|min:0|max:100',
            'xp_reward' => 'required|integer|min:0',
            'energy_cost' => 'required|integer|min:0',
        ]);

        $arena_level->update($request->all());

        return redirect()->route('admin.arena')->with('success', 'Arena Game updated successfully.');
    }

    public function destroy(ArenaLevel $arena_level)
    {
        $arena_level->delete();
        return redirect()->route('admin.arena')->with('success', 'Arena Game deleted successfully.');
    }
}
