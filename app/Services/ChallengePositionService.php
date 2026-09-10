<?php

namespace App\Services;

use App\Models\GameLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChallengePositionService
{
 public const JOURNEY_MAX_LEVEL_NUMBER = 5;

 private const GENERIC_POSITIONS = [
 'general',
 'general interview',
 'interview candidate',
 'interview readiness',
 'interview communication',
 'job interview candidate',
 'local interview candidate',
 'Local interview candidate',
 'local interview readiness',
 'Local interview readiness',
 'Local interview',
 ];

 private const DEFAULT_POSITION_OPTIONS = [
 'Administrative Assistant / LGU Staff',
 'Teacher / Instructor',
 'Nurse / Healthcare Worker',
 'Agricultural Technician',
 'Fisheries Technician',
 'Tourism Staff / Hospitality Worker',
 'Civil Engineer',
 'IT Support Specialist',
 'Customer Service Representative',
 'Software Developer',
 'Accounting Assistant',
 'Sales Representative',
 ];

 public function clean(?string $position): string
 {
 return (string) Str::of((string) $position)->squish()->limit(255, '');
 }

 public function relatedLevels(Collection $levels,?string $position): Collection
 {
 $position = $this->clean($position);
 if ($position === '') {
 return $levels->values();
 }

 $specificMatches = $this->specificMatchingLevels($levels, $position);
 if ($specificMatches->isNotEmpty()) {
 return $specificMatches;
 }

 $generalLevels = $levels
 ->filter(fn ($level): bool => $level instanceof GameLevel && $this->isGeneralPosition($level->target_position))
 ->values();

 return $generalLevels->isNotEmpty()? $generalLevels: $levels->values();
 }

 public function journeyLevels(Collection $levels): Collection
 {
 return $levels
 ->filter(fn ($level): bool => $level instanceof GameLevel && (int) $level->level_number <= self::JOURNEY_MAX_LEVEL_NUMBER)
 ->values();
 }

 public function isJourneyLevel(GameLevel $level): bool
 {
 return (int) $level->level_number <= self::JOURNEY_MAX_LEVEL_NUMBER;
 }

 public function matchingLevels(Collection $levels,?string $position): Collection
 {
 $position = $this->clean($position);
 if ($position === '') {
 return $levels->values();
 }

 return $levels
 ->filter(fn ($level): bool => $level instanceof GameLevel && $this->matches($level, $position))
 ->values();
 }

 public function specificMatchingLevels(Collection $levels,?string $position): Collection
 {
 $position = $this->clean($position);
 if ($position === '') {
 return collect();
 }

 return $levels
 ->filter(fn ($level): bool => $level instanceof GameLevel && $this->matchesSpecificPosition($level, $position))
 ->values();
 }

 public function matches(GameLevel $level,?string $position): bool
 {
 $position = $this->clean($position);
 if ($position === '') {
 return true;
 }

 return $this->isGeneralPosition($level->target_position)
 || $this->matchesSpecificPosition($level, $position);
 }

 public function hasPositionSpecificMatches(Collection $levels,?string $position): bool
 {
 $position = $this->clean($position);
 if ($position === '') {
 return false;
 }

 return $this->specificMatchingLevels($levels, $position)->isNotEmpty();
 }

 public function positionOptions(Collection $levels,?string $currentPosition = null): array
 {
 $options = collect([$currentPosition])
 ->merge($levels->pluck('target_position'))
 ->merge(self::DEFAULT_POSITION_OPTIONS)
 ->map(fn ($position): string => $this->clean($position))
 ->filter()
 ->reject(fn (string $position): bool => $this->isGeneralPosition($position))
 ->unique(fn (string $position): string => Str::lower($position))
 ->values()
 ->take(12)
 ->all();

 return $options!== []? $options: self::DEFAULT_POSITION_OPTIONS;
 }

 public function isGeneralPosition(?string $position): bool
 {
 $normalized = $this->normalize($position);
 if ($normalized === '') {
 return true;
 }

 foreach (self::GENERIC_POSITIONS as $genericPosition) {
 if ($normalized === $genericPosition || Str::contains($normalized, $genericPosition)) {
 return true;
 }
 }

 return false;
 }

 private function matchesSpecificPosition(GameLevel $level, string $position): bool
 {
 $normalizedPosition = $this->normalize($position);
 $normalizedTarget = $this->normalize($level->target_position);

 if ($normalizedTarget === '' || $this->isGeneralPosition($normalizedTarget)) {
 return false;
 }

 if (
 $normalizedTarget === $normalizedPosition
 || Str::contains($normalizedTarget, $normalizedPosition)
 || Str::contains($normalizedPosition, $normalizedTarget)
 ) {
 return true;
 }

 $searchableText = $this->normalize(implode(' ', array_filter([
 $level->target_position,
 $level->title,
 $level->description,
 $level->mission_text,
 $level->skill_focus,
 $level->learning_objective,
 $level->target_tone,
 $level->custom_badge_name,
 ])));

 foreach ($this->aliasTermsFor($normalizedPosition) as $aliasTerm) {
 if (Str::contains($searchableText, $aliasTerm)) {
 return true;
 }
 }

 $tokens = $this->significantTokens($normalizedPosition);
 if ($tokens === []) {
 return false;
 }

 $matchedTokens = 0;
 foreach ($tokens as $token) {
 if (Str::contains($searchableText, $token)) {
 $matchedTokens++;
 }
 }

 $requiredMatches = count($tokens) === 1? 1: min(2, count($tokens));

 return $matchedTokens >= $requiredMatches;
 }

 private function aliasTermsFor(string $normalizedPosition): array
 {
 $groups = [
 ['bpo', 'call center', 'customer service', 'customer support', 'csr', 'technical support', 'support representative'],
 ['software', 'developer', 'programmer', 'web developer', 'information technology', 'technical'],
 ['teacher', 'teaching', 'education', 'school', 'instructor'],
 ['nurse', 'nursing', 'healthcare', 'caregiver', 'medical'],
 ['sales', 'marketing', 'account executive', 'business development'],
 ['accounting', 'bookkeeper', 'finance', 'cashier'],
 ['administrative', 'admin assistant', 'office staff', 'secretary', 'lgu', 'local government'],
 ['data analyst', 'analytics', 'data', 'reporting'],
 ['agriculture', 'agricultural', 'fisheries', 'fishery', 'technician'],
 ['tourism', 'hospitality', 'hotel', 'front desk', 'guest service'],
 ['civil engineer', 'engineering', 'construction', 'infrastructure'],
 ];

 foreach ($groups as $terms) {
 if (Str::contains($normalizedPosition, $terms)) {
 return $terms;
 }
 }

 return [];
 }

 private function significantTokens(string $normalizedText): array
 {
 $stopWords = [
 'and',
 'for',
 'the',
 'job',
 'jobs',
 'role',
 'roles',
 'career',
 'position',
 'positions',
 'local',
 'Local',
 'interview',
 'candidate',
 'applicant',
 'entry',
 'level',
 ];

 $tokens = preg_split('/\s+/', $normalizedText, -1, PREG_SPLIT_NO_EMPTY)?: [];

 return array_values(array_unique(array_filter(
 $tokens,
 fn (string $token): bool => strlen($token) >= 3 &&! in_array($token, $stopWords, true)
 )));
 }

 private function normalize(?string $text): string
 {
 $text = Str::ascii(Str::lower((string) $text));
 $text = preg_replace('/[^a-z0-9]+/', ' ', $text)?? '';
 $text = preg_replace('/\s+/', ' ', $text)?? '';

 return trim($text);
 }
}
