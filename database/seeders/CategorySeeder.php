<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\QuestionDatasetProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->interviewCategories() as $index => $category) {
            $record = Category::firstOrNew([
                'title' => $category['title'],
                'type' => 'core',
            ]);

            if (! $record->exists) {
                $record->status = 'active';
            }

            $record->sort_order = $index + 1;

            if (blank($record->description) && filled($category['description'] ?? null)) {
                $record->description = $category['description'];
            }

            $record->save();
        }

        $arenaCategories = ['Communication', 'Public Speaking', 'Emotional Intelligence', 'Leadership', 'Conflict Resolution'];
        foreach ($arenaCategories as $category) {
            Category::where('title', $category)
                ->where('type', 'arena')
                ->update(['type' => 'game']);

            Category::firstOrCreate(
                ['title' => $category, 'type' => 'game'],
                ['status' => 'active']
            );
        }
    }

    private function interviewCategories(): array
    {
        $categories = $this->manifestCategories();

        if ($categories === []) {
            $categories = $this->normalizedQuestionCategories();
        }

        if ($categories === []) {
            $categories = collect(QuestionDatasetProvider::all())
                ->pluck('category')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $descriptions = $this->categoryDescriptions();

        return collect($categories)
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->unique(fn (string $category) => mb_strtolower($category))
            ->values()
            ->map(fn (string $category) => [
                'title' => $category,
                'description' => $descriptions[mb_strtolower($category)]
                    ?? 'Source-backed interview practice category from the SpeakReady reliable question bank.',
            ])
            ->all();
    }

    private function manifestCategories(): array
    {
        try {
            $disk = Storage::disk('datasets');
            $manifestPath = collect($disk->files('manifests'))
                ->filter(fn (string $path) => preg_match('/^manifests\/speakready_reliable_questions_\d{4}-\d{2}-\d{2}\.json$/', $path))
                ->sortDesc()
                ->values()
                ->first();

            if (! $manifestPath) {
                return [];
            }

            $manifest = json_decode($disk->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

            return is_array($manifest['categories'] ?? null) ? $manifest['categories'] : [];
        } catch (Throwable) {
            return [];
        }
    }

    private function normalizedQuestionCategories(): array
    {
        try {
            $disk = Storage::disk('datasets');
            $path = collect($disk->allFiles('normalized/questions'))
                ->filter(fn (string $path) => str_ends_with($path, '/speakready_reliable_questions.jsonl'))
                ->sortDesc()
                ->values()
                ->first();

            if (! $path) {
                return [];
            }

            return collect(preg_split('/\R/u', $disk->get($path)) ?: [])
                ->map(fn (string $line) => trim($line))
                ->filter()
                ->map(fn (string $line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
                ->pluck('category')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function categoryDescriptions(): array
    {
        return collect(QuestionDatasetProvider::all())
            ->filter(fn (array $dataset) => filled($dataset['category'] ?? null))
            ->mapWithKeys(fn (array $dataset) => [
                mb_strtolower((string) $dataset['category']) => (string) ($dataset['description'] ?? ''),
            ])
            ->all();
    }
}
