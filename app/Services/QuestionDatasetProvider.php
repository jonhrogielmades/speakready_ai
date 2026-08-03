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
        'general_interview_official' => 'ph_job_interview',
        'candidate_questions' => 'ph_job_interview',
        'ph_bpo_communication' => 'ph_bpo_communication',
        'ph_it_programming' => 'ph_it_programming',
        'ph_scholarship' => 'ph_scholarship',
        'ph_college_admission' => 'ph_college_admission',
    ];

    private static ?array $storageQuestionBank = null;

    public static function all(): array
    {
        return self::mergeStorageQuestionBank([
            'ph_job_interview' => [
                'key' => 'ph_job_interview',
                'name' => 'PH Job Interview Questions',
                'category' => 'Job Interview',
                'country' => 'Philippines',
                'source_type' => 'philippines_career_question_bank',
                'description' => 'Common interview questions and answer guidance from Philippine career platforms.',
                'sources' => [
                    [
                        'name' => 'JobStreet Philippines Career Advice',
                        'url' => 'https://ph.jobstreet.com/career-advice/article/job-interview-questions-answers',
                        'note' => 'Common job interview questions for job seekers in the Philippines.',
                    ],
                    [
                        'name' => 'Michael Page Philippines Career Advice',
                        'url' => 'https://www.michaelpage.com.ph/advice/career-advice/interview/common-job-interview-questions-philippines',
                        'note' => 'General interview questions and sample-answer guidance for Philippine candidates.',
                    ],
                    [
                        'name' => 'Bossjob Philippines Career Advice',
                        'url' => 'https://bossjob.ph/blog/job-search-tips/5623/interview-questions-philippines/',
                        'note' => 'Common and industry-specific interview questions for Filipino job seekers.',
                    ],
                ],
                'default_skills' => ['Communication', 'Role Fit', 'Self Awareness', 'STAR Method'],
                'questions' => [
                    [
                        'question_text' => 'Tell me about yourself and why this role in the Philippines fits your next step.',
                        'type' => 'Personal',
                        'difficulty' => 'Easy',
                        'expected_guide' => 'Summarize relevant background, key skills, one concrete achievement, and why the role fits your next step.',
                        'mapped_skills' => ['Self Introduction', 'Communication', 'Role Fit'],
                    ],
                    [
                        'question_text' => 'Why should a Philippine employer hire you for this role?',
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
                        'question_text' => 'Where do you see yourself in five years, and how does this fit your career path in the Philippines?',
                        'type' => 'Personal',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Show realistic career direction, growth mindset, and alignment with the role and organization.',
                        'mapped_skills' => ['Career Planning', 'Role Fit', 'Commitment'],
                    ],
                    [
                        'question_text' => 'Why did you leave your previous job, and what are you looking for in your next Philippine workplace?',
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
                        'question_text' => 'What Philippine work setup helps you do your best work: onsite, hybrid, remote, shifting, or regular hours?',
                        'type' => 'Personal',
                        'difficulty' => 'Easy',
                        'expected_guide' => 'Describe preferred working conditions honestly while matching realistic traits of the target workplace.',
                        'mapped_skills' => ['Self Awareness', 'Culture Fit', 'Communication'],
                    ],
                ],
            ],
            'ph_bpo_communication' => [
                'key' => 'ph_bpo_communication',
                'name' => 'PH BPO and Communication',
                'category' => 'BPO / Customer Support',
                'country' => 'Philippines',
                'source_type' => 'philippines_competency_source',
                'description' => 'Communication and customer-contact prompts grounded in Philippine BPO interview guidance and TESDA competency standards.',
                'sources' => [
                    [
                        'name' => 'Bossjob Philippines Career Advice',
                        'url' => 'https://bossjob.ph/blog/job-search-tips/5623/interview-questions-philippines/',
                        'note' => 'Includes BPO and customer-facing interview questions for Philippine job seekers.',
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
            'ph_it_programming' => [
                'key' => 'ph_it_programming',
                'name' => 'PH IT and Programming',
                'category' => 'IT/Programming',
                'country' => 'Philippines',
                'source_type' => 'philippines_it_interview_source',
                'description' => 'IT and web-development interview prompts from Philippine career guidance, with communication expectations from local competency standards.',
                'sources' => [
                    [
                        'name' => 'Bossjob Philippines Career Advice',
                        'url' => 'https://bossjob.ph/blog/job-search-tips/5623/interview-questions-philippines/',
                        'note' => 'Includes IT and web development interview guidance for Filipino job seekers.',
                    ],
                    [
                        'name' => 'JobStreet Philippines Career Advice',
                        'url' => 'https://ph.jobstreet.com/career-advice/article/job-interview-questions-answers',
                        'note' => 'General questions and answer structure used for Philippine interview preparation.',
                    ],
                    [
                        'name' => 'TESDA Contact Center Services NC II Training Regulation',
                        'url' => 'https://www.tesda.gov.ph/Downloadables/TR%20Contact%20Center%20Services%20NC%20II.pdf',
                        'note' => 'Useful for customer-facing technical support and communication competencies.',
                    ],
                ],
                'default_skills' => ['Technical Communication', 'Problem Solving', 'Learning Agility', 'Tradeoffs'],
                'questions' => [
                    [
                        'question_text' => 'How do you stay updated with new technologies?',
                        'type' => 'Technical',
                        'difficulty' => 'Easy',
                        'expected_guide' => 'Mention credible learning habits, recent tools or frameworks, and how you apply learning in projects.',
                        'mapped_skills' => ['Learning Agility', 'Technical Awareness', 'Initiative'],
                    ],
                    [
                        'question_text' => 'Tell me about a web development project for a Philippine class, client, employer, or startup where you had to balance speed, quality, and maintainability.',
                        'type' => 'Behavioral',
                        'difficulty' => 'Hard',
                        'expected_guide' => 'Use STAR and explain constraints, tradeoffs, technical decisions, and measurable outcome.',
                        'mapped_skills' => ['Technical Judgment', 'Tradeoffs', 'STAR Method'],
                    ],
                    [
                        'question_text' => 'Describe a time you debugged a difficult technical issue.',
                        'type' => 'Behavioral',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Explain symptoms, investigation steps, root cause, fix, verification, and prevention.',
                        'mapped_skills' => ['Debugging', 'Problem Solving', 'Ownership'],
                    ],
                    [
                        'question_text' => 'How do you explain a technical tradeoff to a non-technical stakeholder, client, or manager in a Philippine workplace?',
                        'type' => 'Situational',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Use plain language, business impact, options, risks, and recommendation.',
                        'mapped_skills' => ['Technical Communication', 'Stakeholder Management', 'Judgment'],
                    ],
                    [
                        'question_text' => 'What would you do if requirements changed late in a sprint?',
                        'type' => 'Situational',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Mention clarification, impact assessment, prioritization, communication, and documentation.',
                        'mapped_skills' => ['Agile Collaboration', 'Prioritization', 'Communication'],
                    ],
                ],
            ],
            'ph_scholarship' => [
                'key' => 'ph_scholarship',
                'name' => 'PH Scholarship Applications',
                'category' => 'Scholarship Interview',
                'country' => 'Philippines',
                'source_type' => 'philippines_official_scholarship_source',
                'description' => 'Scholarship practice prompts grounded in official CHED and DOST-SEI scholarship information.',
                'sources' => [
                    [
                        'name' => 'CHED SIKAP FAQ',
                        'url' => 'https://sikap.ched.gov.ph/faqs',
                        'note' => 'Official FAQ for CHED scholarship program questions, eligibility, privileges, and priority areas.',
                    ],
                    [
                        'name' => 'CHED Bagong Pilipinas Merit Scholarship FAQ',
                        'url' => 'https://bpms.ched.gov.ph/faqs',
                        'note' => 'Official scholarship FAQ for higher education and TVET tracks.',
                    ],
                    [
                        'name' => 'DOST-SEI Scholarship Helpdesk',
                        'url' => 'https://helpdesk.sei.dost.gov.ph/',
                        'note' => 'Official DOST-SEI scholarship helpdesk and FAQs.',
                    ],
                ],
                'default_skills' => ['Academic Motivation', 'Service Orientation', 'Goal Clarity', 'Accountability'],
                'questions' => [
                    [
                        'question_text' => 'What scholarship are you applying for, and why does it fit your academic or career plan?',
                        'type' => 'Personal',
                        'difficulty' => 'Easy',
                        'expected_guide' => 'Name the program, connect it to your course or goals, and explain why you are a strong fit.',
                        'mapped_skills' => ['Goal Clarity', 'Academic Motivation', 'Communication'],
                    ],
                    [
                        'question_text' => 'How will you maintain strong academic performance if you are selected?',
                        'type' => 'Situational',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Discuss study systems, time management, support network, and accountability.',
                        'mapped_skills' => ['Accountability', 'Time Management', 'Academic Readiness'],
                    ],
                    [
                        'question_text' => 'How does your chosen field contribute to your community or to the Philippines?',
                        'type' => 'Personal',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Connect the field to public value, local needs, service, innovation, or workforce contribution.',
                        'mapped_skills' => ['Service Orientation', 'Purpose', 'Communication'],
                    ],
                    [
                        'question_text' => 'Tell me about a time you overcame a challenge in school.',
                        'type' => 'Behavioral',
                        'difficulty' => 'Medium',
                        'expected_guide' => 'Use STAR and show resilience, learning strategy, and outcome.',
                        'mapped_skills' => ['Resilience', 'STAR Method', 'Growth Mindset'],
                    ],
                    [
                        'question_text' => 'What obligations or requirements do you expect to manage as a scholar?',
                        'type' => 'Situational',
                        'difficulty' => 'Hard',
                        'expected_guide' => 'Show awareness of compliance, grades, documents, program rules, and responsible communication.',
                        'mapped_skills' => ['Accountability', 'Policy Awareness', 'Professionalism'],
                    ],
                ],
            ],
            'ph_college_admission' => [
                'key' => 'ph_college_admission',
                'name' => 'PH College Admission',
                'category' => 'College Admission',
                'country' => 'Philippines',
                'source_type' => 'philippines_official_admission_source',
                'description' => 'College-admission practice prompts grounded in official Philippine admissions information.',
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
                        'note' => 'Official education and literacy survey context for the Philippines.',
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
            $targetKey = self::storageTargetDatasetKey((string) ($question['dataset_key'] ?? ''));
            if (! $targetKey || ! isset($datasets[$targetKey])) {
                continue;
            }

            $normalized = self::normalizeStorageQuestion($question, $bank['sources'] ?? []);
            if (! $normalized) {
                continue;
            }

            $datasets[$targetKey]['questions'][] = $normalized;
            $recordsByDataset[$targetKey] = ($recordsByDataset[$targetKey] ?? 0) + 1;
            foreach ($normalized['source_keys'] ?? [] as $sourceKey) {
                $sourceKeysByDataset[$targetKey][$sourceKey] = true;
            }
        }

        foreach ($datasets as $key => $dataset) {
            if (($recordsByDataset[$key] ?? 0) <= 0) {
                continue;
            }

            $datasets[$key]['questions'] = collect($dataset['questions'] ?? [])
                ->filter(fn (array $question) => filled($question['question_text'] ?? null))
                ->unique(fn (array $question) => mb_strtolower(trim((string) $question['question_text'])))
                ->values()
                ->all();

            $datasets[$key]['sources'] = self::mergeDatasetSources(
                $dataset['sources'] ?? [],
                array_keys($sourceKeysByDataset[$key] ?? []),
                $bank['sources'] ?? []
            );

            $datasets[$key]['storage_question_bank'] = [
                'dataset' => $bank['manifest']['dataset'] ?? 'speakready_reliable_questions',
                'version' => $bank['manifest']['version'] ?? null,
                'records_used' => $recordsByDataset[$key],
                'manifest_path' => $bank['manifest_path'] ?? null,
                'normalized_path' => $bank['normalized_path'] ?? null,
            ];
        }

        return $datasets;
    }

    private static function storageTargetDatasetKey(string $datasetKey): ?string
    {
        return self::STORAGE_DATASET_MAP[$datasetKey] ?? null;
    }

    private static function normalizeStorageQuestion(array $question, array $sources): ?array
    {
        $questionText = trim((string) ($question['question_text'] ?? ''));
        if ($questionText === '') {
            return null;
        }

        $sourceKeys = array_values(array_filter(array_map(
            fn ($sourceKey) => trim((string) $sourceKey),
            (array) ($question['source_keys'] ?? [])
        )));
        $primarySource = null;
        foreach ($sourceKeys as $sourceKey) {
            if (isset($sources[$sourceKey])) {
                $primarySource = $sources[$sourceKey];
                break;
            }
        }

        $mappedSkills = $question['mapped_skills'] ?? [];
        if (is_string($mappedSkills)) {
            $mappedSkills = array_map('trim', explode(',', $mappedSkills));
        }

        return [
            'question_text' => $questionText,
            'type' => trim((string) ($question['type'] ?? 'Behavioral')) ?: 'Behavioral',
            'difficulty' => ucfirst(strtolower(trim((string) ($question['difficulty'] ?? 'Medium')))),
            'expected_guide' => trim((string) ($question['expected_guide'] ?? '')),
            'mapped_skills' => array_values(array_filter((array) $mappedSkills)),
            'source_name' => $primarySource['name'] ?? 'SpeakReady reliable question bank',
            'source_url' => $primarySource['url'] ?? null,
            'source_type' => 'speakready_reliable_question_bank',
            'source_keys' => $sourceKeys,
            'dataset_record_id' => $question['id'] ?? null,
            'provenance' => $question['provenance'] ?? 'adapted_practice_prompt',
        ];
    }

    private static function mergeDatasetSources(array $existingSources, array $sourceKeys, array $indexedSources): array
    {
        $sources = $existingSources;
        $seen = collect($sources)
            ->map(fn (array $source) => mb_strtolower((string) ($source['url'] ?? $source['name'] ?? '')))
            ->filter()
            ->flip()
            ->all();

        foreach ($sourceKeys as $sourceKey) {
            $source = $indexedSources[$sourceKey] ?? null;
            if (! $source) {
                continue;
            }

            $dedupeKey = mb_strtolower((string) ($source['url'] ?? $source['name'] ?? $sourceKey));
            if ($dedupeKey !== '' && isset($seen[$dedupeKey])) {
                continue;
            }

            $sources[] = [
                'name' => $source['name'] ?? $sourceKey,
                'url' => $source['url'] ?? null,
                'note' => 'Reliable question source snapshot: '.($source['reliability'] ?? $source['source_type'] ?? 'source-backed'),
            ];
            $seen[$dedupeKey] = true;
        }

        return $sources;
    }

    private static function storageQuestionBank(): array
    {
        if (self::$storageQuestionBank !== null) {
            return self::$storageQuestionBank;
        }

        try {
            $disk = Storage::disk('datasets');
            $manifestPath = self::latestQuestionBankManifestPath($disk);
            if (! $manifestPath) {
                return self::$storageQuestionBank = [];
            }

            $manifest = json_decode($disk->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $normalizedPath = data_get($manifest, 'normalized_files.0.path');
            if (! is_string($normalizedPath) || ! $disk->exists($normalizedPath)) {
                return self::$storageQuestionBank = [];
            }

            $sourceIndexPath = data_get($manifest, 'raw_source_index.path');
            $sources = self::indexedQuestionSources($disk, is_string($sourceIndexPath) ? $sourceIndexPath : null);
            $questions = self::readQuestionJsonl($disk->get($normalizedPath));

            return self::$storageQuestionBank = [
                'manifest' => $manifest,
                'manifest_path' => $manifestPath,
                'normalized_path' => $normalizedPath,
                'sources' => $sources,
                'questions' => $questions,
            ];
        } catch (Throwable $exception) {
            Log::warning('Reliable question bank could not be loaded; using built-in question packs.', [
                'error' => $exception->getMessage(),
            ]);

            return self::$storageQuestionBank = [];
        }
    }

    private static function latestQuestionBankManifestPath($disk): ?string
    {
        return collect($disk->files('manifests'))
            ->filter(fn (string $path) => preg_match('/^manifests\/speakready_reliable_questions_\d{4}-\d{2}-\d{2}\.json$/', $path))
            ->sortDesc()
            ->values()
            ->first();
    }

    private static function indexedQuestionSources($disk, ?string $sourceIndexPath): array
    {
        if (! $sourceIndexPath || ! $disk->exists($sourceIndexPath)) {
            return [];
        }

        $index = json_decode($disk->get($sourceIndexPath), true, 512, JSON_THROW_ON_ERROR);

        return collect($index['sources'] ?? [])
            ->filter(fn (array $source) => filled($source['key'] ?? null))
            ->mapWithKeys(fn (array $source) => [(string) $source['key'] => $source])
            ->all();
    }

    private static function readQuestionJsonl(string $contents): array
    {
        return collect(preg_split('/\R/u', $contents) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR))
            ->filter(fn ($row) => is_array($row) && filled($row['question_text'] ?? null))
            ->values()
            ->all();
    }

    public static function find(?string $key): ?array
    {
        if (!$key || $key === 'auto') {
            return null;
        }

        return self::all()[$key] ?? null;
    }

    public static function exists(?string $key): bool
    {
        return self::find($key) !== null;
    }

    public static function forCategory(Category $category): array
    {
        $key = self::defaultKeyForCategory($category->title);
        return self::all()[$key] ?? self::all()['ph_job_interview'];
    }

    public static function defaultKeyForCategory(?string $categoryTitle): string
    {
        $title = strtolower((string) $categoryTitle);

        return match (true) {
            str_contains($title, 'scholar') => 'ph_scholarship',
            str_contains($title, 'college'), str_contains($title, 'admission') => 'ph_college_admission',
            str_contains($title, 'it'), str_contains($title, 'program'), str_contains($title, 'software'), str_contains($title, 'web') => 'ph_it_programming',
            str_contains($title, 'bpo'), str_contains($title, 'customer support'), str_contains($title, 'contact center'), str_contains($title, 'call center') => 'ph_bpo_communication',
            str_contains($title, 'communication'), str_contains($title, 'public speaking'), str_contains($title, 'conflict') => 'ph_bpo_communication',
            default => 'ph_job_interview',
        };
    }

    public static function promptContext(array $dataset): string
    {
        $sources = collect($dataset['sources'] ?? [])
            ->map(fn (array $source) => "- {$source['name']}: ".($source['url'] ?? 'private source snapshot')." ({$source['note']})")
            ->implode("\n");

        $examples = collect($dataset['questions'] ?? [])
            ->take(8)
            ->map(fn (array $question) => "- {$question['question_text']} [{$question['type']}, {$question['difficulty']}]")
            ->implode("\n");
        $questionBank = $dataset['storage_question_bank'] ?? null;
        $questionBankSummary = is_array($questionBank)
            ? "Reliable question-bank version: ".($questionBank['version'] ?? 'unknown')
                ."; normalized records connected to this source pack: ".($questionBank['records_used'] ?? 0)
                ."; manifest: ".($questionBank['manifest_path'] ?? 'private dataset manifest')."\n"
            : '';

        return "Source pack: {$dataset['name']} ({$dataset['country']})\n"
            . "Description: {$dataset['description']}\n"
            . $questionBankSummary
            . "Trusted public sources:\n{$sources}\n"
            . "Representative question patterns:\n{$examples}\n"
            . "Rules: Generate Philippines-relevant practice questions grounded in these sources. Do not claim the wording is a direct quote from a source unless it exactly is. Do not claim to reproduce confidential, leaked, or actual protected exam items. If the source is an official FAQ or competency standard, adapt the same topic or competency into an interview-practice question.";
    }

    public static function sourceMetadata(array $dataset): array
    {
        $source = $dataset['sources'][0] ?? [];

        return [
            'source_name' => $source['name'] ?? $dataset['name'] ?? null,
            'source_url' => $source['url'] ?? null,
            'source_type' => $dataset['source_type'] ?? 'dataset',
        ];
    }

    public static function fallbackQuestion(array $dataset, Category $category, string $position, string $difficulty): array
    {
        $questions = collect($dataset['questions'] ?? []);
        $difficulty = ucfirst(strtolower($difficulty));

        $question = $questions->firstWhere('difficulty', $difficulty)
            ?? $questions->first()
            ?? [
                'question_text' => "For a {$position} role, describe a situation where you used {$category->title}. What action did you take and what result followed?",
                'type' => 'Behavioral',
                'difficulty' => $difficulty,
                'expected_guide' => 'Use a specific example, explain your action, and include the result.',
                'mapped_skills' => $dataset['default_skills'] ?? ['Communication'],
            ];

        return array_merge(self::sourceMetadata($dataset), $question, [
            'category' => $dataset['category'] ?? $category->title,
        ]);
    }

    public static function preparedQuestions(string $key): array
    {
        $dataset = self::find($key);

        if (!$dataset) {
            return [];
        }

        $metadata = self::sourceMetadata($dataset);

        return collect($dataset['questions'] ?? [])
            ->map(fn (array $question) => array_merge($metadata, $question, [
                'category' => $dataset['category'] ?? 'Community Datasets',
            ]))
            ->all();
    }
}
