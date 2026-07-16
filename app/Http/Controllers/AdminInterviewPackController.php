<?php

namespace App\Http\Controllers;

use App\Models\InterviewPack;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function generate(Request $request)
    {
        $data = $request->validate([
            '_pack_modal_id' => 'nullable|string|max:80',
            'target_role' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'role_family' => 'nullable|string|max:255',
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'interview_focus' => 'required|string|max:255',
            'context' => 'nullable|string|max:3000',
            'pack_count' => 'required|integer|min:1|max:5',
            'question_count' => 'required|integer|min:3|max:10',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'pressure_mode' => 'nullable|boolean',
            'ai_provider' => 'nullable|string|max:50',
        ]);

        $provider = trim((string) ($data['ai_provider'] ?? '')) ?: env('AI_PROVIDER', 'gemini');
        $aiDrafts = $this->generatePackDraftsWithAi($data, $provider);
        $fallbackDrafts = $this->fallbackPackDrafts($data);
        $source = empty($aiDrafts) ? 'fallback' : 'ai';
        $drafts = collect($aiDrafts)
            ->concat($fallbackDrafts)
            ->take((int) $data['pack_count'])
            ->values();

        $created = $drafts
            ->map(function (array $draft, int $index) use ($data) {
                $normalized = $this->normalizeGeneratedPack($draft, $data, $index);

                return InterviewPack::create([
                    'name' => $normalized['name'],
                    'slug' => $this->uniqueSlug($normalized['slug'] ?: $normalized['name']),
                    'company' => $normalized['company'],
                    'role_family' => $normalized['role_family'],
                    'difficulty' => $normalized['difficulty'],
                    'interview_focus' => $normalized['interview_focus'],
                    'company_persona' => $normalized['company_persona'],
                    'question_types' => $normalized['question_types'],
                    'sample_questions' => $normalized['sample_questions'],
                    'description' => $normalized['description'],
                    'pressure_mode' => (bool) ($data['pressure_mode'] ?? false),
                    'status' => $data['status'],
                ]);
            });

        $message = $source === 'ai'
            ? "Generated {$created->count()} interview pack(s) using AI with validation and fallback safeguards. Review them before relying on them for live users."
            : "Generated {$created->count()} interview pack(s) with reliable fallback content because AI was unavailable.";

        return redirect()->route('admin.packs.index')->with('success', $message);
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

    private function generatePackDraftsWithAi(array $data, string $provider): array
    {
        try {
            $response = json_decode(AIService::generateJson($this->aiPackPrompt($data), $provider), true);
        } catch (\Throwable $e) {
            Log::warning('Admin interview pack AI generation failed; falling back.', [
                'provider' => $provider,
                'target_role' => $data['target_role'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (! is_array($response)) {
            return [];
        }

        $packs = $response['packs'] ?? $response;

        if (! is_array($packs)) {
            return [];
        }

        if (array_key_exists('name', $packs)) {
            $packs = [$packs];
        }

        return collect($packs)
            ->filter(fn ($draft) => is_array($draft))
            ->values()
            ->all();
    }

    private function aiPackPrompt(array $data): string
    {
        $company = trim((string) ($data['company'] ?? ''));
        $roleFamily = trim((string) ($data['role_family'] ?? ''));
        $context = trim((string) ($data['context'] ?? ''));

        return 'Create accurate, job-related interview practice packs for SpeakReady AI admins. '
            .'Return ONLY valid JSON. No markdown. No commentary. '
            .'Do not claim leaked, exact, confidential, or proprietary company questions. '
            .'Use only realistic public interview patterns and role-relevant competencies. '
            .'Do not include discriminatory, protected-class, medical, family, age, religion, nationality, or illegal questions. '
            .'Each question must ask one clear thing and be suitable for a real mock interview. '
            .'Calibrate difficulty: easy = foundational, medium = evidence/tradeoffs, hard = ambiguity/judgment/impact. '
            ."Generate {$data['pack_count']} pack(s), each with exactly {$data['question_count']} sample questions. "
            .'Use this schema exactly: {"packs":[{"name":"","slug":"","company":"","role_family":"","difficulty":"medium","interview_focus":"","company_persona":"","question_types":["Behavioral","Situational"],"sample_questions":[""],"description":""}]}. '
            .'Inputs: '
            .json_encode([
                'target_role' => $data['target_role'],
                'company' => $company ?: null,
                'role_family' => $roleFamily ?: null,
                'difficulty' => $data['difficulty'],
                'interview_focus' => $data['interview_focus'],
                'extra_context' => $context ?: null,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function normalizeGeneratedPack(array $draft, array $requestData, int $index): array
    {
        $fallbacks = $this->fallbackPackDrafts($requestData);
        $fallback = $fallbacks[$index] ?? $fallbacks[0];
        $questionTypes = $this->cleanList($draft['question_types'] ?? [], $fallback['question_types']);
        $difficulty = strtolower(trim((string) ($draft['difficulty'] ?? $requestData['difficulty'])));
        $sampleQuestions = $this->cleanSampleQuestions(
            $draft['sample_questions'] ?? [],
            $requestData,
            (int) $requestData['question_count'],
            $questionTypes
        );

        return [
            'name' => $this->cleanText($draft['name'] ?? null, $fallback['name'], 255),
            'slug' => Str::slug($this->cleanText($draft['slug'] ?? null, '', 255)),
            'company' => $this->nullableCleanText($draft['company'] ?? ($requestData['company'] ?? null), 255),
            'role_family' => $this->cleanText($draft['role_family'] ?? null, $fallback['role_family'], 255),
            'difficulty' => in_array($difficulty, ['easy', 'medium', 'hard'], true)
                ? $difficulty
                : $requestData['difficulty'],
            'interview_focus' => $this->cleanText($draft['interview_focus'] ?? null, $requestData['interview_focus'], 255),
            'company_persona' => $this->nullableCleanText($draft['company_persona'] ?? $fallback['company_persona'], 255),
            'question_types' => $questionTypes,
            'sample_questions' => $sampleQuestions,
            'description' => $this->cleanText($draft['description'] ?? null, $fallback['description'], 1000),
        ];
    }

    private function fallbackPackDrafts(array $data): array
    {
        $count = (int) $data['pack_count'];
        $targetRole = trim((string) $data['target_role']);
        $company = trim((string) ($data['company'] ?? ''));
        $roleFamily = trim((string) ($data['role_family'] ?? '')) ?: $targetRole;
        $types = $this->fallbackQuestionTypes($data['interview_focus'], $targetRole);
        $packs = [];

        for ($index = 0; $index < $count; $index++) {
            $suffix = $count > 1 ? ' '.($index + 1) : '';
            $nameParts = array_filter([$company, $targetRole, $data['interview_focus']]);
            $name = trim(implode(' ', $nameParts))." Practice Pack{$suffix}";

            $packs[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'company' => $company ?: null,
                'role_family' => $roleFamily,
                'difficulty' => $data['difficulty'],
                'interview_focus' => $data['interview_focus'],
                'company_persona' => $company ?: "{$roleFamily} hiring panel",
                'question_types' => $types,
                'sample_questions' => $this->fallbackSampleQuestions($data, $types, (int) $data['question_count'], $index),
                'description' => "Structured {$data['difficulty']} practice for {$targetRole}, focused on {$data['interview_focus']} with evidence-based interview questions.",
            ];
        }

        return $packs;
    }

    private function fallbackQuestionTypes(string $focus, string $targetRole): array
    {
        $text = strtolower($focus.' '.$targetRole);

        if (str_contains($text, 'technical') || str_contains($text, 'engineer') || str_contains($text, 'developer')) {
            return ['Technical', 'Behavioral', 'Situational'];
        }

        if (str_contains($text, 'lead') || str_contains($text, 'manager')) {
            return ['Behavioral', 'Leadership', 'Situational'];
        }

        if (str_contains($text, 'customer') || str_contains($text, 'support') || str_contains($text, 'service')) {
            return ['Behavioral', 'Situational', 'Customer Service'];
        }

        return ['Behavioral', 'Situational', 'Role Fit'];
    }

    private function fallbackSampleQuestions(array $data, array $types, int $count, int $offset = 0): array
    {
        $role = trim((string) $data['target_role']);
        $company = trim((string) ($data['company'] ?? 'the organization'));
        $focus = trim((string) $data['interview_focus']);
        $difficulty = trim((string) $data['difficulty']);

        $questions = [
            "Tell me about a time you demonstrated {$focus} in a {$role} context.",
            "Describe a situation where you had to make a tradeoff while working toward a {$role} outcome.",
            "How would you handle a high-priority request from {$company} when your current workload is already full?",
            "Walk me through the evidence you would share to show you are ready for this {$role} role.",
            "Tell me about a time you received feedback and changed your approach.",
            "What would you prioritize in your first 30 days as a {$role}, and why?",
            "Describe a difficult stakeholder or customer interaction and how you managed it.",
            "How do you measure whether your work in {$role} is successful?",
            "Give an example of a mistake you made, what you learned, and what changed afterward.",
            "For a {$difficulty} interview, what is the strongest example you can give that proves your fit for {$focus}?",
        ];

        $rotated = array_merge(
            array_slice($questions, $offset % count($questions)),
            array_slice($questions, 0, $offset % count($questions))
        );

        return collect($rotated)
            ->take(max(3, $count))
            ->values()
            ->all();
    }

    private function cleanList(mixed $value, array $fallback): array
    {
        $items = is_array($value)
            ? $value
            : preg_split('/[,;\r\n]+/', (string) $value);

        $cleaned = collect($items ?: [])
            ->map(fn ($item) => $this->cleanText(is_scalar($item) ? (string) $item : null, '', 40))
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        return $cleaned ?: $fallback;
    }

    private function cleanSampleQuestions(mixed $value, array $data, int $count, array $types): array
    {
        $items = is_array($value)
            ? $value
            : preg_split('/\r\n|\r|\n/', (string) $value);

        $cleaned = collect($items ?: [])
            ->map(fn ($item) => $this->cleanText(is_scalar($item) ? (string) $item : null, '', 400))
            ->filter(fn ($question) => str_ends_with($question, '?') || strlen($question) >= 20)
            ->unique()
            ->values();

        if ($cleaned->count() < $count) {
            $cleaned = $cleaned->merge($this->fallbackSampleQuestions($data, $types, $count));
        }

        return $cleaned->filter()->unique()->take($count)->values()->all();
    }

    private function cleanText(mixed $value, string $fallback, int $limit): string
    {
        $text = is_scalar($value)
            ? trim((string) preg_replace('/\s+/', ' ', (string) $value))
            : '';

        if ($text === '') {
            $text = $fallback;
        }

        return Str::limit($text, $limit, '');
    }

    private function nullableCleanText(mixed $value, int $limit): ?string
    {
        $text = is_scalar($value)
            ? trim((string) preg_replace('/\s+/', ' ', (string) $value))
            : '';

        return $text === '' ? null : Str::limit($text, $limit, '');
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
