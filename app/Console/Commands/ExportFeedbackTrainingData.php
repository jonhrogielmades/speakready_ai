<?php

namespace App\Console\Commands;

use App\Models\InterviewAnswer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ExportFeedbackTrainingData extends Command
{
    protected $signature = 'ai:export-feedback-training
        {--output=normalized/training/feedback_train.jsonl : Path on the private datasets disk.}
        {--status=approved,archived : Comma-separated audit statuses to trust as labels.}
        {--include-unreviewed : Include under_review and flagged rows too.}
        {--limit=0 : Maximum rows to export. Use 0 for all.}';

    protected $description = 'Export reviewed interview answer feedback as JSONL training data for the local feedback model.';

    public function handle(): int
    {
        $disk = Storage::disk('datasets');
        $output = trim((string) $this->option('output')) ?: 'normalized/training/feedback_train.jsonl';
        $limit = max(0, (int) $this->option('limit'));
        $includeUnreviewed = (bool) $this->option('include-unreviewed');
        $statuses = array_values(array_filter(array_map(
            fn (string $status): string => trim($status),
            explode(',', (string) $this->option('status'))
        )));

        $directory = trim(dirname(str_replace('\\', '/', $output)), '.');
        if ($directory !== '') {
            $disk->makeDirectory($directory);
        }

        $rows = [];
        $query = InterviewAnswer::query()
            ->with(['question.category', 'interviewSession.category', 'interviewSession.score'])
            ->whereNotNull('answer_text')
            ->whereNotNull('score')
            ->whereNotNull('clarity_score')
            ->whereNotNull('relevance_score')
            ->whereNotNull('grammar_score')
            ->orderBy('id');

        if (InterviewAnswer::hasColumn('is_skipped')) {
            $query->where(function ($inner): void {
                $inner->whereNull('is_skipped')->orWhere('is_skipped', false);
            });
        }

        if (! $includeUnreviewed && InterviewAnswer::hasColumn('audit_status') && $statuses !== []) {
            $query->whereIn('audit_status', $statuses);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->chunkById(200, function ($answers) use (&$rows): void {
            foreach ($answers as $answer) {
                $row = $this->trainingRow($answer);
                if ($row !== null) {
                    $rows[] = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
                }
            }
        });

        $contents = $rows === [] ? '' : implode("\n", $rows)."\n";
        $disk->put($output, $contents);

        $manifestPath = preg_replace('/\.jsonl$/', '', $output) ?: $output;
        $manifestPath .= '_manifest.json';
        $disk->put($manifestPath, json_encode([
            'dataset' => 'speakready_feedback_training',
            'version' => now()->toDateString(),
            'created_at' => now()->toIso8601String(),
            'record_count' => count($rows),
            'source' => 'interview_answers',
            'label_policy' => $includeUnreviewed ? 'all_scored_answers' : 'reviewed_feedback_only',
            'output_path' => $output,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $this->info("Exported ".count($rows)." feedback training rows to datasets:{$output}");
        $this->line("Manifest written to datasets:{$manifestPath}");

        if (count($rows) < 25) {
            $this->warn('This is enough to test the pipeline, but collect at least 100 reviewed answers before trusting the model.');
        }

        return self::SUCCESS;
    }

    private function trainingRow(InterviewAnswer $answer): ?array
    {
        $answerText = trim((string) $answer->answer_text);
        if ($answerText === '') {
            return null;
        }

        $session = $answer->interviewSession;
        $question = $answer->question;
        $sessionScore = $session?->score;
        $professionalism = $this->scoreValue(
            $this->columnExists('interview_answers', 'professionalism_score')
                ? $answer->getAttribute('professionalism_score')
                : ($sessionScore?->professionalism_score ?? $answer->score)
        );
        $starScore = $this->scoreValue($this->starScoreFrom($answer, $sessionScore?->star_method_score));

        return [
            'schema_version' => 1,
            'source' => 'interview_answers',
            'input' => [
                'answer_id' => $answer->id,
                'session_id' => $answer->interview_session_id,
                'question' => trim((string) ($question?->question_text ?? '')),
                'question_type' => trim((string) ($question?->type ?? '')),
                'expected_guide' => trim((string) ($question?->expected_guide ?? '')),
                'mapped_skills' => array_values(array_filter((array) ($question?->mapped_skills ?? []))),
                'category' => trim((string) ($question?->category?->title ?? $session?->category?->title ?? '')),
                'target_position' => trim((string) ($session?->target_position ?? '')),
                'difficulty' => trim((string) ($session?->difficulty ?? $question?->difficulty ?? '')),
                'interview_focus' => trim((string) ($session?->interview_focus ?? '')),
                'company_persona' => trim((string) ($session?->company_persona ?? '')),
                'answer' => $answerText,
                'response_mode' => trim((string) ($answer->response_mode ?? 'text')),
                'voice_duration' => (int) ($answer->voice_duration ?? 0),
                'wpm' => (int) ($answer->wpm ?? 0),
                'filler_words_count' => (int) ($answer->filler_words_count ?? 0),
                'pause_count' => (int) ($answer->pause_count ?? 0),
            ],
            'output' => [
                'score' => $this->scoreValue($answer->score),
                'clarity_score' => $this->scoreValue($answer->clarity_score),
                'relevance_score' => $this->scoreValue($answer->relevance_score),
                'grammar_score' => $this->scoreValue($answer->grammar_score),
                'professionalism_score' => $professionalism,
                'star_method_score' => $starScore,
                'ai_feedback' => trim((string) ($answer->ai_feedback ?? '')),
            ],
            'metadata' => [
                'audit_status' => $answer->audit_status ?? null,
                'scoring_confidence' => $this->scoreValue($answer->scoring_confidence ?? 0),
                'created_at' => optional($answer->created_at)->toIso8601String(),
            ],
        ];
    }

    private function starScoreFrom(InterviewAnswer $answer, mixed $sessionStarScore): int
    {
        $analysis = is_array($answer->star_analysis) ? $answer->star_analysis : [];
        if ($analysis !== []) {
            $present = 0;
            foreach (['situation', 'task', 'action', 'result'] as $part) {
                $value = strtolower(trim((string) ($analysis[$part] ?? '')));
                if ($value !== '' && ! in_array($value, ['missing', 'none', 'no', 'false', '0'], true)) {
                    $present++;
                }
            }

            return $present * 25;
        }

        return $this->scoreValue($sessionStarScore ?? 0);
    }

    private function scoreValue(mixed $value): int
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return 0;
        }

        return max(0, min(100, (int) round((float) $value)));
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }
}
