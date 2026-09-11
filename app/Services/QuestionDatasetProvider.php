<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class QuestionDatasetProvider
{
 private const STORAGE_DATASET_MAP = [
 'ph_job_interview' => 'ph_job_interview',
 'ph_job_interview_expanded' => 'ph_job_interview',
 'general_interview_official' => 'ph_job_interview',
 'candidate_questions' => 'ph_job_interview',
 'ph_bpo_communication' => 'ph_bpo_communication',
 'ph_college_admission' => 'ph_college_admission',
 'ph_school_admission_expanded' => 'ph_college_admission',
 ];

 private static?array $storageQuestionBank = null;
 private static?array $datasetCache = null;

 public static function all(): array
 {
 if (self::$datasetCache !== null && ! app()->runningUnitTests()) {
 return self::$datasetCache;
 }

 $datasets = self::mergeStorageQuestionBank([
 'ph_job_interview' => [
 'key' => 'ph_job_interview',
 'name' => 'Job Interview Questions',
 'category' => 'Job Interview',
 'country' => 'Philippines',
 'source_type' => 'career_question_bank',
 'description' => 'Common interview questions and answer guidance from career platforms.',
 'sources' => [
 [
 'name' => 'JobStreet Career Advice',
 'url' => 'https://ph.jobstreet.com/career-advice/article/job-interview-questions-answers',
 'note' => 'Common job interview questions for job seekers.',
 ],
 [
 'name' => 'Michael Page Career Advice',
 'url' => 'https://www.michaelpage.com.ph/advice/career-advice/interview/common-job-interview-questions',
 'note' => 'General interview questions and sample-answer guidance for candidates.',
 ],
 [
 'name' => 'Bossjob Career Advice',
 'url' => 'https://bossjob.ph/blog/job-search-tips/5623/interview-questions/',
 'note' => 'Common and industry-specific interview questions for Filipino job seekers.',
 ],
 ],
 'default_skills' => ['Communication', 'Role Fit', 'Self Awareness', 'STAR Method'],
 'questions' => [
 [
 'question_text' => 'Tell me about yourself and why this role in your Southern Leyte or Philippine context fits your next step.',
 'type' => 'Personal',
 'difficulty' => 'Easy',
 'expected_guide' => 'Summarize relevant background, key skills, one concrete achievement, and why the role fits your next step.',
 'mapped_skills' => ['Self Introduction', 'Communication', 'Role Fit'],
 ],
 [
 'question_text' => 'Why should a employer hire you for this role?',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Connect role requirements to specific experience, strengths, measurable results, and motivation for the company.',
 'mapped_skills' => ['Role Fit', 'Persuasion', 'Evidence'],
 ],
 [
 'question_text' => 'What is your greatest strength?',
 'type' => 'Personal',
 'difficulty' => 'Easy',
 'expected_guide' => 'Name one relevant strength, support it with a specific example, and tie it to the target role.',
 'mapped_skills' => ['Self Awareness', 'Evidence', 'Role Fit'],
 ],
 [
 'question_text' => 'What is your greatest weakness?',
 'type' => 'Personal',
 'difficulty' => 'Medium',
 'expected_guide' => 'Share a real but manageable weakness, explain concrete improvement actions, and show learning or progress.',
 'mapped_skills' => ['Self Awareness', 'Growth Mindset', 'Professionalism'],
 ],
 [
 'question_text' => 'Can you describe a challenge you faced at work, school, training, or internship and how you solved it?',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Use STAR: situation, task, action, result. Include ownership, decision-making, and measurable impact.',
 'mapped_skills' => ['Problem Solving', 'STAR Method', 'Impact'],
 ],
 [
 'question_text' => 'Where do you see yourself in five years, and how does this fit your Southern Leyte or Philippine career path?',
 'type' => 'Personal',
 'difficulty' => 'Medium',
 'expected_guide' => 'Show realistic career direction, growth mindset, and alignment with the role and organization.',
 'mapped_skills' => ['Career Planning', 'Role Fit', 'Commitment'],
 ],
 [
 'question_text' => 'Why did you leave your previous job, and what are you looking for in your next workplace?',
 'type' => 'Situational',
 'difficulty' => 'Medium',
 'expected_guide' => 'Keep the answer professional, forward-looking, and focused on growth or better alignment.',
 'mapped_skills' => ['Professionalism', 'Communication', 'Judgment'],
 ],
 [
 'question_text' => 'Give an example of how you acted like a team player.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Describe team context, your contribution, collaboration habits, and the outcome.',
 'mapped_skills' => ['Teamwork', 'Communication', 'STAR Method'],
 ],
 [
 'question_text' => 'What work setup helps you do your best work: onsite, hybrid, remote, shifting, or regular hours?',
 'type' => 'Personal',
 'difficulty' => 'Easy',
 'expected_guide' => 'Describe preferred working conditions honestly while matching realistic traits of the target workplace.',
 'mapped_skills' => ['Self Awareness', 'Culture Fit', 'Communication'],
 ],
 ],
 ],
 'ph_bpo_communication' => [
 'key' => 'ph_bpo_communication',
 'name' => 'BPO and Communication',
 'category' => 'BPO / Customer Support',
 'country' => 'Philippines',
 'source_type' => 'competency_source',
 'description' => 'Communication and customer-contact prompts grounded in BPO interview guidance and TESDA competency standards.',
 'sources' => [
 [
 'name' => 'Bossjob Career Advice',
 'url' => 'https://bossjob.ph/blog/job-search-tips/5623/interview-questions/',
 'note' => 'Includes BPO and customer-facing interview questions for local job seekers.',
 ],
 [
 'name' => 'TESDA Contact Center Services NC II Training Regulation',
 'url' => 'https://www.tesda.gov.ph/Downloadables/TR%20Contact%20Center%20Services%20NC%20II.pdf',
 'note' => 'Official competency basis for communication, listening, customer service, and oral English performance.',
 ],
 [
 'name' => 'TESDA Assessment and Certification FAQ',
 'url' => 'https://www.tesda.gov.ph/About/Tesda/127',
 'note' => 'Official basis for assessment methods such as interview, oral questioning, and demonstration.',
 ],
 ],
 'default_skills' => ['Clarity', 'Listening', 'Customer Service', 'Professionalism'],
 'questions' => [
 [
 'question_text' => 'How do you handle irate customers in a BPO or customer-support setting?',
 'type' => 'Situational',
 'difficulty' => 'Medium',
 'expected_guide' => 'Show empathy, active listening, calm tone, policy awareness, and a clear resolution path.',
 'mapped_skills' => ['Customer Service', 'Emotional Control', 'Problem Solving'],
 ],
 [
 'question_text' => 'Tell me about a time you had to explain something clearly to a confused customer or teammate.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Use a specific example showing clarity, conciseness, audience awareness, and confirmation of understanding.',
 'mapped_skills' => ['Clarity', 'Conciseness', 'Listening'],
 ],
 [
 'question_text' => 'How do you confirm that you understood a customer concern before giving a solution?',
 'type' => 'Situational',
 'difficulty' => 'Easy',
 'expected_guide' => 'Mention paraphrasing, clarifying questions, checking details, and confirming next steps.',
 'mapped_skills' => ['Active Listening', 'Customer Service', 'Accuracy'],
 ],
 [
 'question_text' => 'Describe a time you followed a procedure while still making the customer feel heard.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Balance compliance with empathy; include the procedure, communication approach, and result.',
 'mapped_skills' => ['Process Discipline', 'Empathy', 'Professionalism'],
 ],
 [
 'question_text' => 'How do you adapt your communication style for local or international customers with different backgrounds?',
 'type' => 'Situational',
 'difficulty' => 'Hard',
 'expected_guide' => 'Discuss audience awareness, plain language, tone, pacing, and cultural sensitivity.',
 'mapped_skills' => ['Audience Awareness', 'Adaptability', 'Cross-Cultural Communication'],
 ],
 ],
 ],
 'ph_college_admission' => [
 'key' => 'ph_college_admission',
 'name' => 'College Admission',
 'category' => 'College Admission',
 'country' => 'Philippines',
 'source_type' => 'official_admission_source',
 'description' => 'College-admission practice prompts grounded in official admissions information.',
 'sources' => [
 [
 'name' => 'UPCAT Official Admissions Bulletin',
 'url' => 'https://upcat.up.edu.ph/htmls/aboutupcat.html',
 'note' => 'Official UP admissions process, subtests, forms, and degree-program selection context.',
 ],
 [
 'name' => 'CHED Scholarship and Program Information',
 'url' => 'https://legacy.ched.gov.ph/merit-scholarship/',
 'note' => 'Official CHED context for incoming college students and priority programs.',
 ],
 [
 'name' => 'PSA Functional Literacy, Education, and Mass Media Survey',
 'url' => 'https://psa.gov.ph/survey',
 'note' => 'Official education and literacy survey context.',
 ],
 ],
 'default_skills' => ['Academic Readiness', 'Program Fit', 'Self Awareness', 'Communication'],
 'questions' => [
 [
 'question_text' => 'Why are you interested in this degree program?',
 'type' => 'Personal',
 'difficulty' => 'Easy',
 'expected_guide' => 'Connect interests, strengths, academic preparation, and career direction to the program.',
 'mapped_skills' => ['Program Fit', 'Academic Motivation', 'Communication'],
 ],
 [
 'question_text' => 'How have your senior high school experiences prepared you for college?',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Give examples from classes, projects, leadership, service, or independent learning.',
 'mapped_skills' => ['Academic Readiness', 'Evidence', 'Self Awareness'],
 ],
 [
 'question_text' => 'How would you contribute to a diverse university community?',
 'type' => 'Personal',
 'difficulty' => 'Medium',
 'expected_guide' => 'Discuss collaboration, values, background, interests, and concrete contributions.',
 'mapped_skills' => ['Community Fit', 'Communication', 'Self Awareness'],
 ],
 [
 'question_text' => 'If your first degree choice is not available, how would you evaluate your alternatives?',
 'type' => 'Situational',
 'difficulty' => 'Hard',
 'expected_guide' => 'Show realistic decision-making based on strengths, interests, career path, and program requirements.',
 'mapped_skills' => ['Decision Making', 'Adaptability', 'Program Fit'],
 ],
 [
 'question_text' => 'Describe a challenge that shaped your readiness for university life.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Use STAR and focus on resilience, study habits, responsibility, and lessons learned.',
 'mapped_skills' => ['Resilience', 'Academic Readiness', 'STAR Method'],
 ],
 ],
 ],
 ]);

 if (! app()->runningUnitTests()) {
 self::$datasetCache = $datasets;
 }

 return $datasets;
 }

 private static function mergeStorageQuestionBank(array $datasets): array
 {
 $bank = self::storageQuestionBank();
 if ($bank === [] || empty($bank['questions'])) {
 return $datasets;
 }

 $sourceKeysByDataset = [];
 $recordsByDataset = [];
 foreach ($bank['questions'] as $question) {
 $targetKey = self::storageTargetDatasetKey((string) ($question['dataset_key']?? ''));
 if (! $targetKey ||! isset($datasets[$targetKey])) {
 continue;
 }

 $normalized = self::normalizeStorageQuestion($question, $bank['sources']?? []);
 if (! $normalized) {
 continue;
 }

 $datasets[$targetKey]['questions'][] = $normalized;
 $recordsByDataset[$targetKey] = ($recordsByDataset[$targetKey]?? 0) + 1;
 foreach ($normalized['source_keys']?? [] as $sourceKey) {
 $sourceKeysByDataset[$targetKey][$sourceKey] = true;
 }
 }

 foreach ($datasets as $key => $dataset) {
 if (($recordsByDataset[$key]?? 0) <= 0) {
 continue;
 }

 $datasets[$key]['questions'] = self::uniqueQuestionRows($dataset['questions']?? []);

 $datasets[$key]['sources'] = self::mergeDatasetSources(
 $dataset['sources']?? [],
 array_keys($sourceKeysByDataset[$key]?? []),
 $bank['sources']?? []
 );

 $datasets[$key]['storage_question_bank'] = [
 'dataset' => $bank['manifest']['dataset']?? 'speakready_reliable_questions',
 'version' => $bank['manifest']['version']?? null,
 'records_used' => $recordsByDataset[$key],
 'manifest_path' => $bank['manifest_path']?? null,
 'normalized_path' => $bank['normalized_path']?? null,
 ];
 }

 return $datasets;
 }

 private static function storageTargetDatasetKey(string $datasetKey):?string
 {
 return self::STORAGE_DATASET_MAP[$datasetKey]?? null;
 }

 private static function normalizeStorageQuestion(array $question, array $sources):?array
 {
 $questionText = trim((string) ($question['question_text']?? ''));
 if ($questionText === '') {
 return null;
 }

 $sourceKeys = array_values(array_filter(array_map(
 fn ($sourceKey) => trim((string) $sourceKey),
 (array) ($question['source_keys']?? [])
 )));
 $primarySource = null;
 foreach ($sourceKeys as $sourceKey) {
 if (isset($sources[$sourceKey])) {
 $primarySource = $sources[$sourceKey];
 break;
 }
 }

 $mappedSkills = $question['mapped_skills']?? [];
 if (is_string($mappedSkills)) {
 $mappedSkills = array_map('trim', explode(',', $mappedSkills));
 }

 return [
 'question_text' => $questionText,
 'type' => trim((string) ($question['type']?? 'Behavioral'))?: 'Behavioral',
 'difficulty' => ucfirst(strtolower(trim((string) ($question['difficulty']?? 'Medium')))),
 'expected_guide' => trim((string) ($question['expected_guide']?? '')),
 'mapped_skills' => array_values(array_filter((array) $mappedSkills)),
 'source_name' => $primarySource['name']?? 'SpeakReady reliable question bank',
 'source_url' => $primarySource['url']?? null,
 'source_type' => 'speakready_reliable_question_bank',
 'source_keys' => $sourceKeys,
 'dataset_record_id' => $question['id']?? null,
 'provenance' => $question['provenance']?? 'adapted_practice_prompt',
 ];
 }

 private static function mergeDatasetSources(array $existingSources, array $sourceKeys, array $indexedSources): array
 {
 $sources = $existingSources;
 $seen = collect($sources)
 ->map(fn (array $source) => mb_strtolower((string) ($source['url']?? $source['name']?? '')))
 ->filter()
 ->flip()
 ->all();

 foreach ($sourceKeys as $sourceKey) {
 $source = $indexedSources[$sourceKey]?? null;
 if (! $source) {
 continue;
 }

 $dedupeKey = mb_strtolower((string) ($source['url']?? $source['name']?? $sourceKey));
 if ($dedupeKey!== '' && isset($seen[$dedupeKey])) {
 continue;
 }

 $sources[] = [
 'name' => $source['name']?? $sourceKey,
 'url' => $source['url']?? null,
 'note' => 'Reliable question source snapshot: '.($source['reliability']?? $source['source_type']?? 'source-backed'),
 ];
 $seen[$dedupeKey] = true;
 }

 return $sources;
 }

 private static function storageQuestionBank(): array
 {
 if (self::$storageQuestionBank!== null) {
 return self::$storageQuestionBank;
 }

 try {
 $disk = Storage::disk('datasets');
 $manifestPath = self::latestQuestionBankManifestPath($disk);
 if (! $manifestPath) {
 return self::$storageQuestionBank = [];
 }

 $manifest = json_decode($disk->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
 $normalizedFiles = collect($manifest['normalized_files']?? [])
 ->filter(fn ($file) => is_array($file)
 && is_string($file['path']?? null)
 && $disk->exists($file['path']))
 ->values()
 ->all();

 if ($normalizedFiles === []) {
 return self::$storageQuestionBank = [];
 }

 $sourceIndexPath = data_get($manifest, 'raw_source_index.path');
 $sources = self::indexedQuestionSources($disk, is_string($sourceIndexPath)? $sourceIndexPath: null);
 $questionRows = [];

 foreach ($normalizedFiles as $file) {
 $path = (string) $file['path'];
 $questionRows = array_merge($questionRows, self::readQuestionRows(
 $disk->get($path),
 (string) ($file['format']?? pathinfo($path, PATHINFO_EXTENSION)),
 $path,
 ));
 }

 $questions = self::uniqueQuestionRows($questionRows);

 if ($questions === []) {
 return self::$storageQuestionBank = [];
 }

 $normalizedPaths = collect($normalizedFiles)
 ->pluck('path')
 ->map(fn ($path) => (string) $path)
 ->values()
 ->all();

 return self::$storageQuestionBank = [
 'manifest' => $manifest,
 'manifest_path' => $manifestPath,
 'normalized_path' => $normalizedPaths[0]?? null,
 'normalized_paths' => $normalizedPaths,
 'sources' => $sources,
 'questions' => $questions,
 ];
 } catch (Throwable $exception) {
 Log::warning('Reliable question bank could not be loaded; using built-in question sets.', [
 'error' => $exception->getMessage(),
 ]);

 return self::$storageQuestionBank = [];
 }
 }

 private static function latestQuestionBankManifestPath($disk):?string
 {
 return collect($disk->files('manifests'))
 ->filter(fn (string $path) => preg_match('/^manifests\/speakready_reliable_questions_\d{4}-\d{2}-\d{2}\.json$/', $path))
 ->sortDesc()
 ->values()
 ->first();
 }

 private static function indexedQuestionSources($disk,?string $sourceIndexPath): array
 {
 if (! $sourceIndexPath ||! $disk->exists($sourceIndexPath)) {
 return [];
 }

 $index = json_decode($disk->get($sourceIndexPath), true, 512, JSON_THROW_ON_ERROR);

 return collect($index['sources']?? [])
 ->filter(fn (array $source) => filled($source['key']?? null))
 ->mapWithKeys(fn (array $source) => [(string) $source['key'] => $source])
 ->all();
 }

 private static function readQuestionJsonl(string $contents): array
 {
 return collect(preg_split('/\R/u', $contents)?: [])
 ->map(fn (string $line) => trim($line))
 ->filter()
 ->map(fn (string $line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
 ->filter(fn ($row) => is_array($row) && filled($row['question_text']?? null))
 ->values()
 ->all();
 }

 private static function readQuestionRows(string $contents, string $format,?string $path = null): array
 {
 return strtolower(trim($format)) === 'csv'? self::readQuestionCsv($contents, $path): self::readQuestionJsonl($contents);
 }

 private static function uniqueQuestionRows(iterable $rows): array
 {
 $questions = [];
 $seen = [];

 foreach ($rows as $row) {
 if (! is_array($row)) {
 continue;
 }

 $questionText = trim((string) ($row['question_text']?? ''));
 $dedupeKey = self::questionDedupeKey($questionText);

 if ($questionText === '' || $dedupeKey === '' || isset($seen[$dedupeKey])) {
 continue;
 }

 $seen[$dedupeKey] = true;
 $row['question_text'] = $questionText;
 $questions[] = $row;
 }

 return $questions;
 }

 private static function questionDedupeKey(mixed $question): string
 {
 return trim((string) preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower((string) $question)));
 }

 private static function questionSelectionScore(array $question, array $dataset, string $position, string $difficulty, array $selectedTypes): int
 {
 $score = 0;
 $questionDifficulty = ucfirst(strtolower(trim((string) ($question['difficulty']?? 'Medium'))));
 $questionType = trim((string) ($question['type']?? ''));
 $sourceType = mb_strtolower((string) ($question['source_type']?? $dataset['source_type']?? ''));

 if ($questionDifficulty === $difficulty) {
 $score += 40;
 }

 if ($selectedTypes!== [] && in_array($questionType, $selectedTypes, true)) {
 $score += 30;
 } elseif ($selectedTypes === [] && $questionType!== '') {
 $score += 8;
 }

 if (str_contains($sourceType, 'speakready_reliable_question_bank')) {
 $score += 18;
 } elseif (str_contains($sourceType, 'official') || str_contains($sourceType, 'competency')) {
 $score += 12;
 } elseif ($sourceType!== '') {
 $score += 6;
 }

 if (! empty($question['source_keys']?? [])) {
 $score += 6;
 }

 if (! empty($question['dataset_record_id']?? $question['id']?? null)) {
 $score += 4;
 }

 $roleTokens = self::selectionTokens($position);
 if ($roleTokens!== []) {
 $haystack = implode(' ', [
 (string) ($question['question_text']?? ''),
 (string) ($question['expected_guide']?? ''),
 implode(' ', (array) ($question['mapped_skills']?? [])),
 implode(' ', (array) ($question['archive_roles']?? [])),
 (string) ($question['category']?? $dataset['category']?? ''),
 ]);
 $haystackTokens = array_flip(self::selectionTokens($haystack));
 $matches = 0;

 foreach ($roleTokens as $token) {
 if (isset($haystackTokens[$token])) {
 $matches++;
 }
 }

 $score += min(30, $matches * 8);

 $normalizedPosition = self::questionDedupeKey($position);
 $normalizedHaystack = self::questionDedupeKey($haystack);
 if ($normalizedPosition!== '' && str_contains(" {$normalizedHaystack} ", " {$normalizedPosition} ")) {
 $score += 16;
 }
 }

 return $score;
 }

 private static function selectionTokens(string $text): array
 {
 $stopWords = [
 'about' => true,
 'after' => true,
 'also' => true,
 'and' => true,
 'are' => true,
 'for' => true,
 'from' => true,
 'have' => true,
 'how' => true,
 'interview' => true,
 'into' => true,
 'job' => true,
 'role' => true,
 'that' => true,
 'the' => true,
 'this' => true,
 'what' => true,
 'when' => true,
 'where' => true,
 'with' => true,
 'would' => true,
 'you' => true,
 'your' => true,
 ];

 return collect(preg_split('/[^a-z0-9]+/u', mb_strtolower($text))?: [])
 ->map(fn (string $token) => trim($token))
 ->filter(fn (string $token) => strlen($token) >= 3 && ! isset($stopWords[$token]))
 ->unique()
 ->values()
 ->all();
 }

 private static function readQuestionCsv(string $contents,?string $path = null): array
 {
 $stream = fopen('php://temp', 'r+');
 if (! $stream) {
 return [];
 }

 fwrite($stream, $contents);
 rewind($stream);

 $delimiter = self::detectCsvDelimiter($contents);
 $headers = fgetcsv($stream, 0, $delimiter);
 if (! is_array($headers)) {
 fclose($stream);
 return [];
 }

 $headers = array_map([self::class, 'normalizeCsvHeader'], $headers);
 $rows = [];
 $rowNumber = 0;
 while (($values = fgetcsv($stream, 0, $delimiter))!== false) {
 $rowNumber++;
 if ($values === [null] || $values === []) {
 continue;
 }

 $row = [];
 foreach ($headers as $index => $header) {
 if ($header!== '') {
 $row[$header] = $values[$index]?? null;
 }
 }

 $questionText = self::csvValue($row, 'question_text', 'question');
 if (! $questionText) {
 continue;
 }

 $role = self::csvValue($row, 'role', 'target_role');
 $answer = self::csvValue($row, 'answer', 'expected_answer', 'ideal_answer', 'sample_answer');
 $defaults = self::csvDatasetDefaults($path, $role, $answer);
 $category = self::csvValue($row, 'category')?: ($defaults['category']?? 'Job Interview');
 $sourceKeys = self::csvList($row, 'source_keys');
 if ($sourceKeys === []) {
 $sourceKeys = [$defaults['source_key']];
 }

 $mappedSkills = self::csvList($row, 'mapped_skills');
 if ($mappedSkills === [] && $role) {
 $mappedSkills = [$role, 'Role Knowledge', 'Career Readiness'];
 } elseif ($mappedSkills === []) {
 $mappedSkills = $defaults['mapped_skills']?? [];
 }

 $archiveRoles = self::csvList($row, 'archive_roles');
 if ($archiveRoles === [] && $role) {
 $archiveRoles = [$role];
 } elseif ($archiveRoles === []) {
 $archiveRoles = $defaults['archive_roles']?? [];
 }

 $rows[] = [
 'id' => self::csvValue($row, 'record_id', 'id')?: $defaults['id_prefix'].'-'.str_pad((string) $rowNumber, 4, '0', STR_PAD_LEFT),
 'dataset_key' => self::csvValue($row, 'dataset_key')?: 'ph_job_interview',
 'category' => $category,
 'country' => self::csvValue($row, 'country')?: 'General',
 'question_text' => $questionText,
 'type' => self::csvValue($row, 'type')?: self::csvQuestionType($category, $role, $answer),
 'difficulty' => self::csvValue($row, 'difficulty')?: 'Medium',
 'expected_guide' => self::csvValue($row, 'expected_guide')?: ($answer?: self::csvDefaultExpectedGuide($role, $category)),
 'mapped_skills' => $mappedSkills,
 'source_keys' => $sourceKeys,
 'provenance' => self::csvValue($row, 'provenance')?: $defaults['provenance'],
 'archive_category' => self::csvValue($row, 'archive_category')?: $defaults['archive_category'],
 'archive_record_count' => (int) (self::csvValue($row, 'archive_record_count')?: 0),
 'archive_roles' => $archiveRoles,
 'archive_experience_levels' => self::csvList($row, 'archive_experience_levels'),
 ];
 }

 fclose($stream);

 return $rows;
 }

 private static function detectCsvDelimiter(string $contents): string
 {
 foreach (preg_split('/\R/u', $contents)?: [] as $line) {
 $line = trim($line);
 if ($line === '') {
 continue;
 }

 $counts = [
 ',' => substr_count($line, ','),
 ';' => substr_count($line, ';'),
 "\t" => substr_count($line, "\t"),
 ];
 arsort($counts);

 return (string) array_key_first($counts);
 }

 return ',';
 }

 private static function csvDatasetDefaults(?string $path,?string $role,?string $answer): array
 {
 $normalizedPath = mb_strtolower(str_replace('\\', '/', (string) $path));

 if (str_contains($normalizedPath, 'career_qa_dataset')) {
 return [
 'id_prefix' => 'career-qa-csv',
 'source_key' => 'career_qa_dataset_csv',
 'provenance' => 'user_supplied_career_qa_csv',
 'archive_category' => 'Career QA',
 'category' => 'Job Interview',
 'mapped_skills' => [],
 'archive_roles' => [],
 ];
 }

 if (str_contains($normalizedPath, 'full_interview_questions_dataset')) {
 return [
 'id_prefix' => 'full-interview-csv',
 'source_key' => 'full_interview_questions_dataset_csv',
 'provenance' => 'user_supplied_full_interview_questions_csv',
 'archive_category' => 'Full Interview Questions',
 'category' => 'Job Interview',
 'mapped_skills' => [],
 'archive_roles' => [],
 ];
 }

 if (str_contains($normalizedPath, 'quality_assurance_interview_questions_dataset')) {
 return [
 'id_prefix' => 'qa-interview-csv',
 'source_key' => 'quality_assurance_interview_questions_csv',
 'provenance' => 'user_supplied_quality_assurance_interview_csv',
 'archive_category' => 'Quality Assurance',
 'category' => 'Technical',
 'mapped_skills' => ['Quality Assurance', 'Software Testing', 'Technical Interview'],
 'archive_roles' => ['Quality Assurance'],
 ];
 }

 return [
 'id_prefix' => 'question-csv',
 'source_key' => 'uploaded_hr_interview_questions_archive',
 'provenance' => 'user_supplied_hr_archive_normalized',
 'archive_category' => ($role && $answer)? 'Career QA': null,
 'category' => 'Job Interview',
 'mapped_skills' => [],
 'archive_roles' => [],
 ];
 }

 private static function csvQuestionType(?string $category,?string $role,?string $answer): string
 {
 $categoryType = mb_strtolower(trim((string) $category));

 if (in_array($categoryType, ['behavioral', 'situational', 'technical', 'personal'], true)) {
 return ucfirst($categoryType);
 }

 return ($role && $answer)? 'Technical': 'Behavioral';
 }

 private static function csvDefaultExpectedGuide(?string $role,?string $category): string
 {
 if ($role) {
 return "Frame the answer for the {$role} role. Include a clear explanation, a practical example, and a concise takeaway.";
 }

 if ($category) {
 return "Frame the answer for this {$category} interview prompt. Include a clear explanation, a practical example, and a concise takeaway.";
 }

 return 'Give a clear interview answer with a practical example and a concise takeaway.';
 }

 private static function normalizeCsvHeader(?string $header): string
 {
 $header = preg_replace('/^\xEF\xBB\xBF/u', '', (string) $header);

 return trim((string) preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($header)), '_');
 }

 private static function csvValue(array $row, string...$keys):?string
 {
 foreach ($keys as $key) {
 $value = trim((string) ($row[$key]?? ''));
 if ($value!== '') {
 return $value;
 }
 }

 return null;
 }

 private static function csvList(array $row, string $key): array
 {
 $value = self::csvValue($row, $key);
 if (! $value) {
 return [];
 }

 return collect(preg_split('/\s*[;,]\s*/u', $value)?: [])
 ->map(fn (string $item) => trim($item))
 ->filter()
 ->values()
 ->all();
 }

 public static function find(?string $key):?array
 {
 if (!$key || $key === 'auto') {
 return null;
 }

 return self::all()[$key]?? null;
 }

 public static function exists(?string $key): bool
 {
 return self::find($key)!== null;
 }

 public static function forCategory(Category $category): array
 {
 $key = self::defaultKeyForCategory($category->title);
 return self::all()[$key]?? self::all()['ph_job_interview'];
 }

 public static function defaultKeyForCategory(?string $categoryTitle): string
 {
 $title = strtolower((string) $categoryTitle);

 return match (true) {
 str_contains($title, 'college'), str_contains($title, 'admission') => 'ph_college_admission',
 str_contains($title, 'bpo'), str_contains($title, 'customer support'), str_contains($title, 'contact center'), str_contains($title, 'call center') => 'ph_bpo_communication',
 str_contains($title, 'communication'), str_contains($title, 'public speaking'), str_contains($title, 'conflict') => 'ph_bpo_communication',
 default => 'ph_job_interview',
 };
 }

 public static function promptContext(array $dataset): string
 {
 $sources = collect($dataset['sources']?? [])
 ->map(fn (array $source) => "- {$source['name']}: ".($source['url']?? 'private source snapshot')." ({$source['note']})")
 ->implode("\n");

 $examples = collect($dataset['questions']?? [])
 ->take(8)
 ->map(fn (array $question) => "- {$question['question_text']} [{$question['type']}, {$question['difficulty']}]")
 ->implode("\n");
 $questionBank = $dataset['storage_question_bank']?? null;
 $questionBankSummary = is_array($questionBank)? "Reliable question-bank version: ".($questionBank['version']?? 'unknown')."; normalized records connected to this source dataset: ".($questionBank['records_used']?? 0)."; manifest: ".($questionBank['manifest_path']?? 'private dataset manifest')."\n": '';

 return "Source dataset: {$dataset['name']}\n". "Description: {$dataset['description']}\n". $questionBankSummary. "Trusted public sources:\n{$sources}\n". "Representative question patterns:\n{$examples}\n". "Rules: Generate role-relevant practice questions grounded in these sources. Do not claim the wording is a direct quote from a source unless it exactly is. Do not claim to reproduce confidential, leaked, or actual protected exam items. If the source is an official FAQ or competency standard, adapt the same topic or competency into an interview-practice question.";
 }

 public static function sourceMetadata(array $dataset): array
 {
 $source = $dataset['sources'][0]?? [];

 return [
 'source_name' => $source['name']?? $dataset['name']?? null,
 'source_url' => $source['url']?? null,
 'source_type' => $dataset['source_type']?? 'dataset',
 ];
 }

 public static function fallbackQuestion(array $dataset, Category $category, string $position, string $difficulty): array
 {
 $questions = collect($dataset['questions']?? []);
 $difficulty = ucfirst(strtolower($difficulty));

 $question = $questions->firstWhere('difficulty', $difficulty)?? $questions->first()?? [
 'question_text' => "For a {$position} role, describe a situation where you used {$category->title}. What action did you take and what result followed?",
 'type' => 'Behavioral',
 'difficulty' => $difficulty,
 'expected_guide' => 'Use a specific example, explain your action, and include the result.',
 'mapped_skills' => $dataset['default_skills']?? ['Communication'],
 ];

 return array_merge(self::sourceMetadata($dataset), $question, [
 'category' => $dataset['category']?? $category->title,
 ]);
 }

 public static function rankedQuestions(array $dataset, string $position, string $difficulty, array $questionTypes = [], int $limit = 1, array $excludeQuestionTexts = []): array
 {
 $limit = max(1, min(30, $limit));
 $difficulty = ucfirst(strtolower(trim($difficulty)))?: 'Medium';
 $selectedTypes = collect($questionTypes)
 ->map(fn ($type) => trim((string) $type))
 ->filter()
 ->values()
 ->all();
 $excluded = collect($excludeQuestionTexts)
 ->map(fn ($question) => self::questionDedupeKey($question))
 ->filter()
 ->flip()
 ->all();
 $metadata = self::sourceMetadata($dataset);
 $questions = collect($dataset['questions']?? [])
 ->filter(fn ($question) => is_array($question));

 if ($selectedTypes!== []) {
 $typedQuestions = $questions->filter(fn (array $question) => in_array(trim((string) ($question['type']?? '')), $selectedTypes, true));
 if ($typedQuestions->isNotEmpty()) {
 $questions = $typedQuestions;
 }
 }

 return $questions
 ->values()
 ->map(function (array $question, int $index) use ($dataset, $metadata, $position, $difficulty, $selectedTypes, $excluded):?array {
 $text = trim((string) ($question['question_text']?? ''));
 $dedupeKey = self::questionDedupeKey($text);

 if ($text === '' || $dedupeKey === '' || isset($excluded[$dedupeKey])) {
 return null;
 }

 return [
 'index' => $index,
 'score' => self::questionSelectionScore($question, $dataset, $position, $difficulty, $selectedTypes),
 'record' => array_merge($metadata, [
 'question_text' => $text,
 'type' => trim((string) ($question['type']?? 'Behavioral'))?: 'Behavioral',
 'difficulty' => ucfirst(strtolower(trim((string) ($question['difficulty']?? $difficulty))))?: $difficulty,
 'expected_guide' => trim((string) ($question['expected_guide']?? '')),
 'mapped_skills' => array_values(array_filter((array) ($question['mapped_skills']?? ($dataset['default_skills']?? [])))),
 'source_name' => $question['source_name']?? $metadata['source_name']?? null,
 'source_url' => $question['source_url']?? $metadata['source_url']?? null,
 'source_type' => $question['source_type']?? $metadata['source_type']?? 'dataset',
 'source_keys' => array_values(array_filter((array) ($question['source_keys']?? []))),
 'dataset_record_id' => $question['dataset_record_id']?? $question['id']?? null,
 'provenance' => $question['provenance']?? null,
 ]),
 ];
 })
 ->filter()
 ->sort(function (array $left, array $right): int {
 if ($left['score'] === $right['score']) {
 return $left['index'] <=> $right['index'];
 }

 return $right['score'] <=> $left['score'];
 })
 ->pluck('record')
 ->unique(fn (array $question) => self::questionDedupeKey($question['question_text']?? ''))
 ->take($limit)
 ->values()
 ->all();
 }

 public static function preparedQuestions(string $key): array
 {
 $dataset = self::find($key);

 if (!$dataset) {
 return [];
 }

 $metadata = self::sourceMetadata($dataset);

 return collect($dataset['questions']?? [])
 ->map(fn (array $question) => array_merge($metadata, $question, [
 'category' => $dataset['category']?? 'Community Datasets',
 ]))
 ->all();
 }
}
