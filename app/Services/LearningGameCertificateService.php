<?php

namespace App\Services;

use App\Models\Category;
use App\Models\GameCertificate;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearningGameCertificateService
{
    public function completedPathStats(User $user, Category $category): ?array
    {
        $levels = $this->certificateLevels($category);
        if ($levels->isEmpty()) {
            return null;
        }

        $progressByLevel = GameProgress::where('user_id', $user->id)
            ->whereIn('game_level_id', $levels->pluck('id'))
            ->get()
            ->keyBy('game_level_id');

        foreach ($levels as $level) {
            $progress = $progressByLevel->get($level->id);
            if (! $progress || (int) $progress->best_score < (int) $level->required_score) {
                return null;
            }
        }

        $latestSession = GameSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('level', fn ($query) => $query->where('category_id', $category->id))
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->first();

        return [
            'level_count' => $levels->count(),
            'average_score' => (int) round($progressByLevel->avg('best_score') ?? 0),
            'final_level' => $levels->last(),
            'completed_at' => $latestSession?->completed_at ?? $latestSession?->updated_at ?? now(),
        ];
    }

    public function isUnlocked(User $user, Category $category): bool
    {
        return $this->completedPathStats($user, $category) !== null;
    }

    public function issueFor(User $user, Category $category): GameCertificate
    {
        $stats = $this->completedPathStats($user, $category);
        abort_unless($stats, 403);

        $certificate = GameCertificate::firstOrNew([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        if (! $certificate->exists) {
            $certificate->certificate_code = $this->uniqueCertificateCode();
            $certificate->issued_at = now();
        }

        $certificate->final_game_level_id = $stats['final_level']->id;
        $certificate->save();

        return $certificate;
    }

    public function pdfBytes(GameCertificate $certificate, User $user, Category $category): string
    {
        $stats = $this->completedPathStats($user, $category);
        abort_unless($stats, 403);

        $completedAt = $stats['completed_at'];
        $issuedAt = $certificate->issued_at ?? now();

        $lines = [
            ['CERTIFICATE OF COMPLETION', 420, 474, 28, 'F2', [0.11, 0.19, 0.34], true],
            ['This certifies that', 420, 408, 15, 'F1', [0.35, 0.41, 0.52], true],
            [$user->name ?: 'SpeakReady Learner', 420, 368, 30, 'F3', [0.06, 0.28, 0.48], true],
            ['has completed the SpeakReady AI challenge path', 420, 324, 15, 'F1', [0.35, 0.41, 0.52], true],
            [$category->title ?: 'Philippines Interview Challenges', 420, 286, 22, 'F2', [0.08, 0.34, 0.39], true],
            ['Completed levels: '.$stats['level_count'].'    Average score: '.$stats['average_score'].'%', 420, 238, 13, 'F1', [0.28, 0.34, 0.44], true],
            ['Completed on '.$completedAt->format('F j, Y').'    Issued on '.$issuedAt->format('F j, Y'), 420, 213, 12, 'F1', [0.38, 0.44, 0.54], true],
            ['Certificate ID: '.$certificate->certificate_code, 420, 118, 11, 'F2', [0.25, 0.31, 0.41], true],
            ['SpeakReady AI', 420, 84, 12, 'F2', [0.08, 0.34, 0.39], true],
        ];

        $stream = $this->certificateBackground();
        foreach ($lines as [$text, $x, $y, $size, $font, $color, $center]) {
            $stream .= $this->text($text, $x, $y, $size, $font, $color, $center);
        }

        return $this->pdfDocument($stream);
    }

    public function filename(Category $category): string
    {
        $slug = Str::slug($category->title ?: 'challenge-certificate') ?: 'challenge-certificate';

        return 'speakready-'.$slug.'-certificate.pdf';
    }

    private function certificateLevels(Category $category): Collection
    {
        return GameLevel::where('category_id', $category->id)
            ->where('is_hidden', false)
            ->orderBy('level_number')
            ->orderBy('id')
            ->get();
    }

    private function uniqueCertificateCode(): string
    {
        do {
            $code = 'SR-CH-'.Str::upper(Str::random(10));
        } while (GameCertificate::where('certificate_code', $code)->exists());

        return $code;
    }

    private function certificateBackground(): string
    {
        return implode("\n", [
            '0.965 0.980 0.992 rg 0 0 842 595 re f',
            '0.075 0.145 0.270 RG 5 w 34 34 774 527 re S',
            '0.090 0.340 0.390 RG 1.5 w 52 52 738 491 re S',
            '0.940 0.760 0.270 rg 52 492 738 8 re f',
            '0.090 0.340 0.390 rg 52 95 738 3 re f',
            '0.090 0.340 0.390 RG 2 w 240 264 m 602 264 l S',
            '0.940 0.760 0.270 RG 1.2 w 312 146 m 530 146 l S',
            '0.090 0.340 0.390 rg 86 432 64 64 re f',
            '0.940 0.760 0.270 rg 696 99 58 58 re f',
        ])."\n";
    }

    private function text(string $text, float $x, float $y, int $size, string $font, array $color, bool $center = false): string
    {
        $text = $this->asciiText($text);
        if ($center) {
            $x -= $this->estimatedTextWidth($text, $size, $font) / 2;
        }

        return sprintf(
            "BT /%s %d Tf %.3F %.3F %.3F rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $this->escapePdfText($text)
        );
    }

    private function estimatedTextWidth(string $text, int $size, string $font): float
    {
        $factor = $font === 'F1' ? 0.48 : 0.54;

        return strlen($text) * $size * $factor;
    }

    private function asciiText(string $text): string
    {
        $text = Str::ascii($text);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return trim($text);
    }

    private function escapePdfText(string $text): string
    {
        return strtr($text, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]);
    }

    private function pdfDocument(string $stream): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R >> >> /Contents 7 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Times-BoldItalic >>',
            "<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream",
        ];

        $pdf = "%PDF-1.4\n%PDF certificate\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $number = $index + 1;
            $pdf .= $number." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }
}
