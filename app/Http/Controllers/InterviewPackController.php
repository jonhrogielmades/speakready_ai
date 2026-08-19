<?php

namespace App\Http\Controllers;

use App\Models\InterviewPack;

class InterviewPackController extends Controller
{
    public function index()
    {
        $packs = InterviewPack::where('status', 'active')
            ->orderBy('company')
            ->orderBy('role_family')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($pack) => $pack->company ?: $pack->role_family ?: 'General');

        return $this->mobileView('user.packs.index', compact('packs'));
    }

    public function practice(InterviewPack $pack)
    {
        if ($pack->status !== 'active') {
            abort(404);
        }

        return redirect()->route('interview.setup', ['pack' => $pack->id]);
    }
}
