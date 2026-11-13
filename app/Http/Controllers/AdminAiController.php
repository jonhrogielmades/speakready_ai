<?php

namespace App\Http\Controllers;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Models\AiProvider;
use App\Models\AiProviderLog;
use App\Services\AiProviderEvaluationService;
use App\Services\AiProviderReliabilityService;
use App\Services\AIService;
use App\Services\CsvExportService;
use App\Support\AiProviderSchema;
use App\Support\ProviderHttpErrorDiagnostic;
use App\Support\RuntimeConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAiController extends Controller
{
 public function providers()
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $activeProvider = AiProvider::safePrimaryOrActive();
 if ($activeProvider &&! AIService::providerIsConfigured($activeProvider->name)) {
 $activeProvider = null;
 }
 $todayStart = today()->startOfDay();
 $tomorrowStart = today()->addDay()->startOfDay();
 $todayLogs = AiProviderLog::where('created_at', '>=', $todayStart)
 ->where('created_at', '<', $tomorrowStart);
 $todayTotals = (clone $todayLogs)
 ->selectRaw("COUNT(*) as total_requests, SUM(CASE WHEN status IN ('success', 'repaired') THEN 1 ELSE 0 END) as successful_requests, SUM(CASE WHEN status IN ('success', 'repaired') THEN 0 ELSE 1 END) as failed_requests, AVG(response_time_ms) as avg_response_time")
 ->first();
 $totalRequests = (int) ($todayTotals->total_requests?? 0);
 $successfulRequests = (int) ($todayTotals->successful_requests?? 0);
 $failedRequests = (int) ($todayTotals->failed_requests?? 0);

 $avgResponseTime = $todayTotals->avg_response_time?? 0;

 $successRate = $totalRequests > 0? round(($successfulRequests / $totalRequests) * 100, 2): null;

 $defaultProviders = collect(AIService::supportedProviderOptions());
 $supportedProviderKeys = $defaultProviders->pluck('key')->all();
 $providers = AiProvider::all()
 ->filter(fn (AiProvider $provider): bool => in_array(AIService::normalizeProviderKey($provider->name), $supportedProviderKeys, true))
 ->values();
 $configuredProviderKeys = $providers
 ->map(fn (AiProvider $provider): string => AIService::normalizeProviderKey($provider->name))
 ->all();
 $availableProviderOptions = $defaultProviders
 ->reject(fn (array $providerOption): bool => in_array($providerOption['key'], $configuredProviderKeys, true))
 ->values();
 $providerKeysById = $providers->mapWithKeys(
 fn (AiProvider $provider): array => [$provider->id => AIService::normalizeProviderKey($provider->name)]
 );
 $todayUsageRows = (clone $todayLogs)
 ->selectRaw("provider_id, endpoint, COUNT(*) as requests_today, SUM(CASE WHEN status IN ('success', 'repaired') THEN 1 ELSE 0 END) as successful_requests, SUM(CASE WHEN status IN ('success', 'repaired') THEN 0 ELSE 1 END) as failed_requests, COALESCE(SUM(response_time_ms), 0) as response_time_total, COUNT(response_time_ms) as response_time_samples, MAX(created_at) as last_activity_at")
 ->groupBy('provider_id', 'endpoint')
 ->get();
 $providerStats = $defaultProviders->map(function (array $providerOption) use ($providers, $providerKeysById, $todayUsageRows) {
 $dbProvider = $providers->first(
 fn (AiProvider $provider): bool => AIService::normalizeProviderKey($provider->name) === $providerOption['key']
 );
 $usageRows = $todayUsageRows->filter(function ($usage) use ($providerKeysById, $providerOption): bool {
 $providerKey = $usage->provider_id === null
 ? AIService::normalizeProviderKey($usage->endpoint)
 : (string) ($providerKeysById[(int) $usage->provider_id]?? AIService::normalizeProviderKey($usage->endpoint));

 return $providerKey === $providerOption['key'];
 });
 $responseTimeSamples = (int) $usageRows->sum('response_time_samples');
 $lastActivityAt = $usageRows->max('last_activity_at');
 $stats = new \stdClass;
 $stats->key = $providerOption['key'];
 $stats->name = $providerOption['label'];
 $stats->model = $providerOption['model']?? 'Not configured';
 $stats->is_configured = (bool) $providerOption['enabled'];
 $stats->has_saved_provider = $dbProvider !== null;
 $stats->is_primary = (bool) ($dbProvider?->is_primary?? false);
 $stats->is_fallback = (bool) ($dbProvider?->is_fallback?? false);
 $stats->requests_today = (int) $usageRows->sum('requests_today');
 $stats->successful_requests = (int) $usageRows->sum('successful_requests');
 $stats->failed_requests = (int) $usageRows->sum('failed_requests');
 $stats->avg_response_time = $responseTimeSamples > 0
 ? (int) round((float) $usageRows->sum('response_time_total') / $responseTimeSamples)
 : null;
 $stats->success_rate = $stats->requests_today > 0
 ? round(($stats->successful_requests / $stats->requests_today) * 100, 2)
 : null;
 $stats->last_activity_at = $lastActivityAt? Carbon::parse($lastActivityAt): null;

 if ($dbProvider) {
 $stats->status = $dbProvider->status!== 'active'
 ? 'inactive'
 : ($stats->is_configured? 'active': 'misconfigured');
 } else {
 $stats->status = $stats->is_configured? 'active': 'unconfigured';
 }

 return $stats;
 });

 return $this->mobileView('admin.ai.providers', compact(
 'providers', 'providerStats', 'availableProviderOptions',
 'activeProvider', 'totalRequests', 'successfulRequests', 'failedRequests',
 'avgResponseTime', 'successRate'
 ));
 }

 public function evaluation(Request $request, AiProviderEvaluationService $evaluationService)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $days = max(7, min(365, (int) $request->integer('days', 30)));
 $runId = $request->integer('run')?: null;
 $dashboard = $evaluationService->dashboard($days, $runId);

 return $this->mobileView('admin.ai.evaluation', $dashboard);
 }

 public function evaluationRealtime(Request $request, AiProviderEvaluationService $evaluationService)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $days = max(7, min(365, (int) $request->integer('days', 30)));
 $runId = $request->integer('run')?: null;
 $dashboard = $evaluationService->dashboard($days, $runId);
 $generatedOutputs = collect($dashboard['generatedOutputs']?? []);

 return response()->json([
 'html' => view('admin.ai.partials.evaluation-content', $dashboard)->render(),
 'updated_at' => now()->format('M d, Y g:i:s A'),
 'total_provider_count' => $generatedOutputs->count(),
 'active_configured_provider_count' => $dashboard['summary']['active_configured_providers']?? 0,
 'minimum_required_provider_count' => $dashboard['summary']['minimum_required_providers']?? 3,
 'panelist_requirement_met' => (bool) ($dashboard['summary']['panelist_requirement_met']?? false),
 'generated_provider_count' => $generatedOutputs->where('has_generated_evidence', true)->count(),
 'generated_question_count' => $generatedOutputs->sum(fn (array $group): int => count($group['questions']?? [])),
 'generated_feedback_count' => $generatedOutputs->sum(fn (array $group): int => count($group['feedback']?? [])),
 'poll_ms' => 8000,
 ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
 }

 public function runEvaluation(AiProviderEvaluationService $evaluationService)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $run = $evaluationService->runUserRequestComparison(auth()->id());
 $message = $run->case_count > 0? 'AI provider comparison completed. Every configured provider was evaluated against the same user request evidence for review and export.': ($run->summary['note']?? 'AI provider comparison did not run because no comparable user request evidence was available.');

 return redirect()
 ->route('admin.ai.evaluation', ['run' => $run->id])
 ->with('message', $message);
 }

 public function smokeEvaluation()
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $providers = collect(AIService::supportedProviderOptions())
 ->filter(fn (array $provider): bool => (bool) ($provider['enabled']?? false))
 ->pluck('key')
 ->values();

 if ($providers->isEmpty()) {
 return redirect()
 ->route('admin.ai.evaluation')
 ->with('error', 'No configured AI API key is available for the live feedback smoke test.');
 }

 $sessionData = [
 'target_position' => 'Customer Service Representative',
 'difficulty' => 'Medium',
 'target_language' => 'en',
 ];
 $answersData = [[
 'id' => 1,
 'question' => 'Tell me about a time you handled a difficult customer.',
 'question_type' => 'behavioral',
 'answer' => 'In my previous role, I listened to an upset customer, apologized for the delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the quick update.',
 'is_skipped' => false,
 'expected_guide' => 'Explain the situation, your action, and result.',
 'mapped_skills' => ['customer service', 'communication'],
 ]];
 $runtimeOptions = [
 'timeout_seconds' => (int) RuntimeConfiguration::get('services.ai_providers.smoke_timeout_seconds', 'AI_PROVIDER_SMOKE_TIMEOUT', 20),
 'deadline_seconds' => (int) RuntimeConfiguration::get('services.ai_providers.smoke_deadline_seconds', 'AI_PROVIDER_SMOKE_DEADLINE_SECONDS', 45),
 'max_attempts' => 2,
 'http_attempts' => 1,
 'retry_delay_ms' => 0,
 'require_provider_narratives' => true,
 'answer_only_completion' => true,
 'allow_provider_completion' => false,
 'module' => 'feedback_smoke',
 ];

 $preparedItem = null;
 $results = $providers->map(function (string $provider) use ($sessionData, $answersData, $runtimeOptions, &$preparedItem): array {
 $startedAt = microtime(true);
 $rejectedDrafts = [];
 $providerRuntimeOptions = $runtimeOptions;
 $providerRuntimeOptions['rejected_response_observer'] = static function (?array $candidate, array $validationErrors, string $observedProvider, int $attempt) use (&$rejectedDrafts, $provider): void {
 if ($observedProvider !== $provider || ! is_array($candidate)) {
 return;
 }
 $item = $candidate['per_question_feedback'][0]?? null;
 if (! is_array($item)) {
 return;
 }
 $fields = array_intersect_key($item, array_flip(['ai_feedback', 'follow_up_question', 'coaching']));
 if ($fields === []) {
 return;
 }
 $encoded = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
 $rejectedDrafts[$attempt] = [
 'attempt' => $attempt,
 'validation_errors' => array_map(
 static fn (string $error): string => mb_substr($error, 0, 220),
 array_slice(array_values(array_filter($validationErrors, 'is_string')), 0, 8)
 ),
 'fields_json' => mb_substr(is_string($encoded) ? $encoded : '', 0, 3500),
 ];
 };

 try {
 $feedback = AIService::generateFeedback($sessionData, $answersData, $provider, true, false, $providerRuntimeOptions);
 $item = $feedback['per_question_feedback'][0]?? [];
 $quality = is_array($item) ? (array) ($item['feedback_quality']?? []) : [];
 $failedChecks = array_keys(array_filter((array) ($quality['checks']?? []), fn ($passed): bool => $passed !== true));
 $usable = is_array($item)
 && (string) ($item['id']?? '') === (string) $answersData[0]['id']
 && ($item['evaluation_source']?? null) === 'ai_evidence_validated'
 && ($item['answer_review_source']?? null) === 'ai_provider'
 && ($item['provider_narratives_verified']?? null) === true
 && ($quality['status']?? null) === 'verified'
 && (int) ($quality['completeness_percent']?? 0) === 100
 && AIService::normalizeProviderKey($feedback['_provider_key']?? null) === $provider;
 if ($usable && $preparedItem === null) {
 $preparedItem = $item;
 $preparedItem['_prepared_provider_key'] = $provider;
 }

 return [
 'provider' => $provider,
 'status' => $usable ? 'ok' : 'failed',
 'api_reachable' => true,
 'json_parse_passed' => true,
 'strict_schema_passed' => $usable,
 'repair_applied' => (bool) ($feedback['_per_answer_provider_retry']?? false),
 'api_status' => 'OK',
 'json_status' => 'OK',
 'strict_schema_status' => $usable ? 'OK' : 'Failed',
 'repair_status' => !empty($feedback['_per_answer_provider_retry']) ? 'Provider retry' : 'No local fallback',
 'final_usable' => $usable,
 'score' => $item['score']?? null,
 'quality' => $quality['completeness_percent']?? null,
 'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
 'rejected_drafts' => array_values($rejectedDrafts),
 'errors' => $usable ? [] : array_merge(
 ['Provider answer review did not pass the saved-answer checks.'],
 $failedChecks === [] ? [] : ['Failed checks: '.implode(', ', $failedChecks).'.']
 ),
 ];
 } catch (\Throwable $error) {
 $failureReasons = $error instanceof AiFeedbackProviderFailureException
 ? (array) ($error->failureReasons()[$provider]?? [])
 : [];
 $validationDetails = $error instanceof AiFeedbackProviderFailureException
 ? (array) ($error->validationErrors()[$provider]?? [])
 : [];
 $httpStatuses = $error instanceof AiFeedbackProviderFailureException
 ? (array) ($error->httpStatuses()[$provider]?? [])
 : [];
 $httpDiagnostics = $error instanceof AiFeedbackProviderFailureException
 ? (array) ($error->httpDiagnostics()[$provider]?? [])
 : [];
 $failureCode = $error instanceof AiFeedbackProviderFailureException
 ? $error->failureCode()
 : 'unexpected_error';
 $parsedJson = in_array('validation', $failureReasons, true);
 $invalidResponse = in_array('invalid_response', $failureReasons, true);
 $apiStatus = match ($failureCode) {
 'provider_rate_limit' => 'Rate limited',
 'provider_timeout' => 'Timed out',
 'provider_network' => 'Network error',
 'provider_circuit_open' => 'Paused',
 'provider_authentication', 'provider_model', 'provider_server_error', 'provider_request_rejected' => 'Fail',
 default => $parsedJson ? 'OK' : 'Unknown',
 };
 $failureNote = match ($failureCode) {
 'provider_authentication' => 'Provider rejected the API credentials. Check its key and permissions.',
 'provider_model' => 'Provider rejected the model. Check the configured model name and access.',
 'provider_rate_limit' => 'Provider rate limit or quota reached. Check quota, then retry.',
 'provider_timeout' => 'Provider request timed out. Check its status and the smoke-test timeout.',
 'provider_network' => 'Could not connect to the provider. Check the endpoint and server network.',
 'provider_circuit_open' => 'Provider circuit is cooling down after failures. Retry after the cooldown.',
 'provider_response_rejected' => 'Parsed JSON failed the saved-answer checks. Inspect validation errors in the server log.',
 'provider_invalid_response' => 'Provider returned no complete valid JSON response. Check output limits and the server log.',
 'provider_server_error' => 'Provider returned a server error. Check its status and retry.',
 'provider_request_rejected' => 'Provider rejected the request. Check its model and structured-output support.',
 default => 'Provider failed for an unknown reason. Inspect the server log.',
 };
 if ($httpStatuses !== []) {
 $failureNote.= ' HTTP '.implode(', ', $httpStatuses).'.';
 }

 return [
 'provider' => $provider,
 'status' => 'failed',
 'api_reachable' => $parsedJson,
 'json_parse_passed' => $parsedJson,
 'strict_schema_passed' => false,
 'repair_applied' => false,
 'api_status' => $apiStatus,
 'json_status' => $parsedJson ? 'OK' : ($invalidResponse ? 'Failed' : 'Not checked'),
 'strict_schema_status' => $parsedJson ? 'Failed' : 'Not checked',
 'repair_status' => 'No local fallback',
 'failure_reasons' => $failureReasons,
 'validation_errors' => $validationDetails,
 'http_statuses' => $httpStatuses,
 'http_diagnostics' => $httpDiagnostics,
 'final_usable' => false,
 'score' => null,
 'quality' => null,
 'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
 'rejected_drafts' => array_values($rejectedDrafts),
 'errors' => array_merge(
 [$failureNote],
 array_map(
 static fn (array $diagnostic): string => 'Provider HTTP error: '.ProviderHttpErrorDiagnostic::describe($diagnostic),
 $httpDiagnostics
 ),
 $validationDetails
 ),
 ];
 }
 })->values()->all();

 $summaryResult = ['status' => 'not_tested', 'note' => 'No verified answer review was available for the final summary test.'];
 if (is_array($preparedItem)) {
 try {
 $summary = AIService::generatePreparedReviewSummary($sessionData, $answersData, [$preparedItem], 45, null, null, 'feedback_smoke');
 $summaryProvider = AIService::normalizeProviderKey($summary['_provider_key']?? null);
 $summaryUsable = $summaryProvider !== ''
 && ($summary['_provider_narratives_verified']?? null) === true
 && AIService::visibleSessionFeedbackIsProviderUsable(
 (array) ($summary['session_feedback']?? []), $answersData
 );
 $summaryResult = [
 'status' => $summaryUsable ? 'ok' : 'failed',
 'provider' => $summaryProvider,
 'note' => $summaryUsable
 ? 'The final session summary passed the saved-answer checks.'
 : 'The final session summary did not pass the saved-answer checks.',
 ];
 } catch (\Throwable) {
 $summaryResult = [
 'status' => 'failed',
 'note' => 'The final session summary could not be generated by a provider.',
 ];
 }
 }

 $usable = collect($results)->where('final_usable', true)->count();

 return redirect()
 ->route('admin.ai.evaluation')
 ->with('message', sprintf('AI provider live answer-review smoke test completed: %d/%d provider(s) produced usable feedback.', $usable, count($results)))
 ->with('ai_provider_smoke_results', $results)
 ->with('ai_provider_smoke_budget_seconds', $runtimeOptions['deadline_seconds'])
 ->with('ai_provider_smoke_summary', $summaryResult);
 }

 public function exportEvaluation(Request $request, AiProviderEvaluationService $evaluationService)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $days = max(7, min(365, (int) $request->integer('days', 30)));
 $runId = $request->integer('run')?: null;
 $rows = $evaluationService->exportRows($days, $runId);
 $fileName = 'ai_provider_evaluation_'.now()->format('Ymd_His').'.csv';

 return response()->stream(function () use ($rows): void {
 $stream = fopen('php://output', 'w');
 CsvExportService::writeRow($stream, AiProviderEvaluationService::EXPORT_COLUMNS);

 foreach ($rows as $row) {
 CsvExportService::writeRow($stream, array_values($row));
 }

 fclose($stream);
 }, 200, [
 'Content-Type' => 'text/csv; charset=UTF-8',
 'Content-Disposition' => "attachment; filename={$fileName}",
 'Cache-Control' => 'no-store, no-cache, must-revalidate',
 ]);
 }

 public function clearEvaluation(AiProviderEvaluationService $evaluationService)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $cleared = $evaluationService->clearAllEvidence();

 return redirect()
 ->route('admin.ai.evaluation')
 ->with('message', sprintf(
 'AI provider evaluation evidence cleared. Removed %d benchmark runs, %d benchmark results, %d generated question markers, and %d generated feedback markers.',
 $cleared['runs'],
 $cleared['results'],
 $cleared['questions'],
 $cleared['feedback']
 ));
 }

 public function evaluationReport(Request $request, AiProviderEvaluationService $evaluationService)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $days = max(7, min(365, (int) $request->integer('days', 30)));
 $runId = $request->integer('run')?: null;
 $dashboard = $evaluationService->dashboard($days, $runId);

 return $this->mobileView('admin.ai.evaluation-report', $dashboard);
 }

 public function storeProvider(Request $request, AiProviderReliabilityService $reliability)
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $validated = $request->validate([
 'name' => 'required|string|max:80',
 'api_endpoint' => 'required|url|max:2048',
 'api_key' => 'required|string|max:8192',
 'status' => 'required|in:active,inactive',
 ]);

 $providerKey = AIService::normalizeProviderKey($validated['name']);
 if (! AIService::providerIsSupported($providerKey)) {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['name' => 'Select one of the supported AI providers.']);
 }

 if (trim($validated['api_key']) === '') {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['api_key' => 'The API key cannot be blank.']);
 }

 $endpointCheck = $reliability->validateEndpoint($providerKey, $validated['api_endpoint']);
 if (! $endpointCheck['valid']) {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['api_endpoint' => $endpointCheck['reason']]);
 }

 if ($this->providerRecordExists($providerKey)) {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['name' => 'This AI provider is already configured. Update the existing provider instead.']);
 }

 DB::transaction(function () use ($providerKey, $validated): void {
 $provider = AiProvider::create([
 'name' => $this->providerLabel($providerKey),
 'api_endpoint' => trim($validated['api_endpoint']),
 'api_key' => Crypt::encryptString(trim($validated['api_key'])),
 'status' => $validated['status'],
 ]);

 if ($provider->status === 'active' && ! AiProvider::where('is_primary', true)->exists()) {
 $provider->update(['is_primary' => true]);
 }
 });

 return redirect()->route('admin.ai.providers')->with('message', 'AI Provider added successfully.');
 }

 public function updateProvider(Request $request, AiProvider $provider, AiProviderReliabilityService $reliability)
 {
 $validated = $request->validate([
 'name' => 'required|string|max:80',
 'api_endpoint' => 'required|url|max:2048',
 'api_key' => 'nullable|string|max:8192',
 'status' => 'required|in:active,inactive',
 ]);

 $providerKey = AIService::normalizeProviderKey($validated['name']);
 if (! AIService::providerIsSupported($providerKey)) {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['name' => 'Select one of the supported AI providers.']);
 }

 $endpointCheck = $reliability->validateEndpoint($providerKey, $validated['api_endpoint']);
 if (! $endpointCheck['valid']) {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['api_endpoint' => $endpointCheck['reason']]);
 }

 if ($this->providerRecordExists($providerKey, $provider->id)) {
 return redirect()->back()
 ->withInput($request->except('api_key'))
 ->withErrors(['name' => 'This AI provider is already configured. Update the existing provider instead.']);
 }

 $data = [
 'name' => $this->providerLabel($providerKey),
 'api_endpoint' => trim($validated['api_endpoint']),
 'status' => $validated['status'],
 ];

 if ($request->filled('api_key')) {
 $data['api_key'] = Crypt::encryptString(trim((string) $request->api_key));
 }

 if ($validated['status'] === 'inactive') {
 $data['is_primary'] = false;
 $data['is_fallback'] = false;
 }

 $provider->update($data);

 return redirect()->route('admin.ai.providers')->with('message', 'Provider updated successfully.');
 }

 public function destroyProvider(AiProvider $provider)
 {
 if ($provider->is_primary) {
 return redirect()->route('admin.ai.providers')->with('error', 'Cannot delete the primary provider. Please set another provider as primary first.');
 }

 if ($provider->is_fallback) {
 return redirect()->route('admin.ai.providers')->with('error', 'Cannot delete the fallback provider. Please set another provider as fallback first.');
 }

 $provider->delete();

 return redirect()->route('admin.ai.providers')->with('message', 'Provider deleted successfully.');
 }

 public function testProvider(AiProvider $provider, AiProviderReliabilityService $reliability)
 {
 if ($error = $this->providerRoutingError($provider, $reliability)) {
 return redirect()->back()->with('error', $error);
 }

 $providerKey = AIService::normalizeProviderKey($provider->name);
 $nonce = Str::lower(Str::random(20));
 $response = json_decode((string) AIService::generateJson(
 "Provider connection verification. Return status ready and nonce {$nonce}. Do not add any other fields.",
 $providerKey,
 [
 'module' => 'provider_connection_test',
 'timeout_seconds' => 20,
 'attempts' => 1,
 'max_providers' => 1,
 'response_validator' => fn (array $result): bool => ($result['status']?? null) === 'ready'
 && is_string($result['nonce']?? null)
 && hash_equals($nonce, $result['nonce']),
 'response_format' => [
 'type' => 'json_schema',
 'json_schema' => [
 'name' => 'provider_connection_test',
 'strict' => true,
 'schema' => [
 'type' => 'object',
 'additionalProperties' => false,
 'properties' => [
 'status' => ['type' => 'string', 'enum' => ['ready']],
 'nonce' => ['type' => 'string'],
 ],
 'required' => ['status', 'nonce'],
 ],
 ],
 ]
 ]
 ), true);

 $verified = is_array($response)
 && ($response['status']?? null) === 'ready'
 && is_string($response['nonce']?? null)
 && hash_equals($nonce, $response['nonce']);

 if (! $verified) {
 return redirect()->back()->with('error', "{$provider->name} connection test failed validation. Review its key, endpoint, model, and provider logs.");
 }

 return redirect()->back()->with('message', "{$provider->name} connection test passed with a schema-validated response.");
 }

 public function setPrimaryProvider(AiProvider $provider, AiProviderReliabilityService $reliability)
 {
 if ($error = $this->providerRoutingError($provider, $reliability)) {
 return redirect()->back()->with('error', $error);
 }

 AiProvider::where('id', '!=', 0)->update(['is_primary' => false]);
 $provider->update(['is_primary' => true]);

 return redirect()->back()->with('message', 'Primary provider updated.');
 }

 public function setFallbackProvider(AiProvider $provider, AiProviderReliabilityService $reliability)
 {
 if ($error = $this->providerRoutingError($provider, $reliability)) {
 return redirect()->back()->with('error', $error);
 }

 AiProvider::where('id', '!=', 0)->update(['is_fallback' => false]);
 $provider->update(['is_fallback' => true]);

 return redirect()->back()->with('message', 'Fallback provider updated.');
 }

 private function providerRecordExists(string $providerKey,?int $exceptId = null): bool
 {
 return AiProvider::query()
 ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
 ->get(['id', 'name'])
 ->contains(fn (AiProvider $provider): bool => AIService::normalizeProviderKey($provider->name) === $providerKey);
 }

 private function providerLabel(string $providerKey): string
 {
 $option = collect(AIService::supportedProviderOptions())->firstWhere('key', $providerKey);

 return (string) ($option['label']?? ucfirst($providerKey));
 }

 private function providerRoutingError(AiProvider $provider, AiProviderReliabilityService $reliability):?string
 {
 $providerKey = AIService::normalizeProviderKey($provider->name);
 if ($provider->status!== 'active'
 || empty($provider->api_key)
 ||! AIService::providerIsSupported($providerKey)
 ||! AIService::providerIsConfigured($providerKey)) {
 return 'Only an active, fully configured provider with a trusted endpoint, model, and API key can be assigned a routing role.';
 }

 $endpointCheck = $reliability->validateEndpoint($providerKey, (string) $provider->api_endpoint);
 if (! $endpointCheck['valid']) {
 return 'This provider cannot be routed because its endpoint is not trusted: '.$endpointCheck['reason'];
 }

 try {
 if (trim(Crypt::decryptString($provider->api_key)) === '') {
 return 'This provider cannot be routed because its API key is empty.';
 }
 } catch (\Throwable) {
 return 'This provider cannot be routed because its API key cannot be decrypted.';
 }

 return null;
 }
}
