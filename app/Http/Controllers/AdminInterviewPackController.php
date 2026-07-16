<?php

namespace App\Http\Controllers;

use App\Models\InterviewPack;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminInterviewPackController extends Controller
{
    public function index(Request $request)
    {
        $query = InterviewPack::query()->withCount('sessions');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($packQuery) use ($search) {
                $packQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('role_family', 'like', "%{$search}%")
                    ->orWhere('interview_focus', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $packs = $query
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('company')
            ->orderBy('role_family')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => InterviewPack::count(),
            'active' => InterviewPack::where('status', 'active')->count(),
            'pressure' => InterviewPack::where('pressure_mode', true)->count(),
            'used_sessions' => InterviewPack::has('sessions')->count(),
        ];

        return view('admin.interview-packs', compact('packs', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPack($request);
        $slug = $this->uniqueSlug($data['slug'] ?: $data['name']);

        InterviewPack::create($this->packPayload($data, $slug));

        return redirect()->route('admin.packs.index')->with('success', 'Interview pack published for users.');
    }

    public function update(Request $request, InterviewPack $pack)
    {
        $data = $this->validatedPack($request, $pack);
        $slug = $data['slug'] ?: $pack->slug;

        if ($slug !== $pack->slug) {
            $slug = $this->uniqueSlug($slug, $pack);
        }

        $pack->update($this->packPayload($data, $slug));

        return redirect()->route('admin.packs.index')->with('success', 'Interview pack updated.');
    }

    public function destroy(InterviewPack $pack)
    {
        if ($pack->sessions()->exists()) {
            $pack->update(['status' => 'inactive']);

            return redirect()
                ->route('admin.packs.index')
                ->with('success', 'Pack has user sessions, so it was safely deactivated instead of deleted.');
        }

        $pack->delete();

        return redirect()->route('admin.packs.index')->with('success', 'Interview pack deleted.');
    }

    private function validatedPack(Request $request, ?InterviewPack $pack = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('interview_packs', 'slug')->ignore($pack?->id),
            ],
            'company' => 'nullable|string|max:255',
            'role_family' => 'nullable|string|max:255',
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'interview_focus' => 'required|string|max:255',
            'company_persona' => 'nullable|string|max:255',
            'question_types_text' => 'nullable|string|max:2000',
            'sample_questions_text' => 'nullable|string|max:12000',
            'description' => 'nullable|string|max:5000',
            'pressure_mode' => 'nullable|boolean',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function packPayload(array $data, string $slug): array
    {
        return [
            'name' => $data['name'],
            'slug' => $slug,
            'company' => $data['company'] ?? null,
            'role_family' => $data['role_family'] ?? null,
            'difficulty' => $data['difficulty'],
            'interview_focus' => $data['interview_focus'],
            'company_persona' => $data['company_persona'] ?? null,
            'question_types' => $this->parseList($data['question_types_text'] ?? null),
            'sample_questions' => $this->parseLines($data['sample_questions_text'] ?? null),
            'description' => $data['description'] ?? null,
            'pressure_mode' => (bool) ($data['pressure_mode'] ?? false),
            'status' => $data['status'],
        ];
    }

    private function uniqueSlug(string $value, ?InterviewPack $pack = null): string
    {
        $base = Str::slug($value) ?: 'interview-pack';
        $slug = $base;
        $suffix = 2;

        while (InterviewPack::where('slug', $slug)
            ->when($pack, fn ($query) => $query->where('id', '!=', $pack->id))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function parseList(?string $value): array
    {
        return collect(preg_split('/[,;\r\n]+/', trim((string) $value)) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function parseLines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', trim((string) $value)) ?: [])
            ->map(fn ($line) => trim((string) preg_replace('/^[\s\-\x{2022}]+/u', '', (string) $line)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
