<?php

namespace App\Http\Controllers;

use App\Models\AiProvider;
use App\Models\AiProviderLog;
use App\Services\AiProviderEvaluationService;
use App\Services\AIService;
use App\Services\CsvExportService;
use App\Support\AiProviderSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AdminAiController extends Controller
{
 public function providers()
 {
 AiProviderSchema::ensure(createIfMissing: true);

 $activeProvider = AiProvider::safePrimaryOrActive();
 $todayStart = today()->startOfDay();
 $tomorrowStart = today()->addDay()->startOfDay();
 $monthStart = now()->startOfMonth();
 $nextMonthStart = now()->addMonthNoOverflow()->startOfMonth();
 $todayLogs = AiProviderLog::where('created_at', '>=', $todayStart)
 ->where('created_at', '<', $tomorrowStart);
 $todayTotals = (clone $todayLogs)
 ->selectRaw("COUNT(*) as total_requests, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests, SUM(CASE WHEN status != 'success' THEN 1 ELSE 0 END) as failed_requests, AVG(response_time_ms) as avg_response_time")
 ->first();
 $totalRequests = (int) ($todayTotals->total_requests?? 0);
 $successfulRequests = (int) ($todayTotals->successful_requests?? 0);
 $failedRequests = (int) ($todayTotals->failed_requests?? 0);

 $avgResponseTime = $todayTotals->avg_response_time?? 0;

 $successRate = $totalRequests > 0? round(($successfulRequests / $totalRequests) * 100, 2): 100;

 $monthlyCost = AiProviderLog::where('created_at', '>=', $monthStart)
 ->where('created_at', '<', $nextMonthStart)
 ->sum('cost')?? 0;

 $moduleUsage = AiProviderLog::selectRaw('module, count(*) as count')
 ->groupBy('module')
 ->get();

 $defaultProviders = collect(AIService::supportedProviderOptions());
 $supportedProviderKeys = $defaultProviders->pluck('key')->all();
 $providers = AiProvider::all()
 ->filter(fn (AiProvider $provider): bool => in_array(AIService::normalizeProviderKey($provider->name), $supportedProviderKeys, true))
 ->values();
 $todayStatsByProvider = (clone $todayLogs)
 ->whereNotNull('provider_id')
 ->selectRaw("provider_id, COUNT(*) as requests_today, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests, AVG(response_time_ms) as avg_response_time")
 ->groupBy('provider_id')
 ->get()
 ->keyBy('provider_id');
 $monthlyCostByProvider = AiProviderLog::where('created_at', '>=', $monthStart)
 ->where('created_at', '<', $nextMonthStart)
 ->whereNotNull('provider_id')
 ->selectRaw('provider_id, SUM(cost) as monthly_cost')
 ->groupBy('provider_id')
 ->pluck('monthly_cost', 'provider_id');
 $openAiProviderIds = $providers
 ->filter(fn (AiProvider $provider): bool => AIService::normalizeProviderKey($provider->name) === 'openai')
 ->pluck('id')
 ->values()
 ->all();
 $openAiLogCounts = AiProviderLog::query()
 ->where(function ($query) use ($openAiProviderIds): void {
 $query->where('endpoint', 'openai');

 if ($openAiProviderIds!== []) {
 $query->orWhereIn('provider_id', $openAiProviderIds);
 }
 })
 ->selectRaw('module, count(*) as count')
 ->groupBy('module')
 ->pluck('count', 'module');
 $openAiProcessConnections = collect(AIService::providerProcessConnections('openai'))
 ->map(function (array $process) use ($openAiLogCounts): array {
 $logModules = array_values(array_filter((array) ($process['log_modules']?? [])));
 $process['request_count'] = collect($logModules)
 ->sum(fn ($module): int => (int) ($openAiLogCounts[$module]?? 0));

 return $process;
 })
 ->values();

 $providerStats = $defaultProviders->map(function (array $providerOption) use ($providers, $todayStatsByProvider, $monthlyCostByProvider) {
 $dbProvider = $providers->first(
 fn (AiProvider $provider): bool => AIService::normalizeProviderKey($provider->name) === $providerOption['key']
 );
 $stats = new \stdClass;
 $stats->name = $providerOption['label'];

 if ($dbProvider) {
 $stats->is_configured = true;
 $stats->status = $dbProvider->status;
 $stats->is_primary = $dbProvider->is_primary;
 $stats->is_fallback = $dbProvider->is_fallback;
 $providerTodayStats = $todayStatsByProvider->get($dbProvider->id);
 $stats->requests_today = (int) ($providerTodayStats->requests_today?? 0);
 $stats->successful_requests = (int) ($providerTodayStats->successful_requests?? 0);
 $stats->avg_response_time = $providerTodayStats->avg_response_time?? 0;
 $stats->success_rate = $stats->requests_today > 0? round(($stats->successful_requests / $stats->requests_today) * 100, 2): 100;
 $stats->monthly_cost = $monthlyCostByProvider[$dbProvider->id]?? 0;
 } else {
 $stats->is_configured = $providerOption['enabled'];
 $stats->status = $providerOption['enabled']? 'active': 'unconfigured';
 $stats->is_primary = false;
 $stats->is_fallback = false;
 $stats->requests_today = 0;
 $stats->successful_requests = 0;
 $stats->avg_response_time = 0;
 $stats->success_rate = 100;
 $stats->monthly_cost = 0;
 }

 return $stats;
 });

 $primary = $providers->first(fn (AiProvider $provider): bool => $provider->is_primary && $provider->status === 'active' &&! empty($provider->api_key));
 $fallback = $providers->first(fn (AiProvider $provider): bool => $provider->is_fallback);

 return $this->mobileView('admin.ai.providers', compact(
 'providers', 'providerStats', 'primary', 'fallback',
 'activeProvider', 'totalRequests', 'successfulRequests', 'failedRequests',
 'avgResponseTime', 'successRate', 'monthlyCost', 'moduleUsage',
 'openAiProcessConnections'
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

 public function storeProvider(Request $request)
 {
 $request->validate([
 'name' => 'required|string',
 'api_endpoint' => 'required|url',
 'api_key' => 'required|string',
 'status' => 'required|in:active,inactive',
 ]);

 if (! AIService::providerIsSupported($request->name)) {
 return redirect()->back()
 ->withInput()
 ->with('error', 'This AI provider is not in the active provider pool. Use OpenAI, Gemini, Groq, or Cohere.');
 }

 $provider = AiProvider::create([
 'name' => $request->name,
 'api_endpoint' => $request->api_endpoint,
 'api_key' => Crypt::encryptString($request->api_key),
 'status' => $request->status,
 ]);

 if (AiProvider::count() == 1 && $provider->status === 'active') {
 $provider->update(['is_primary' => true]);
 }

 return redirect()->route('admin.ai.providers')->with('message', 'AI Provider added successfully.');
 }

 public function updateProvider(Request $request, AiProvider $provider)
 {
 $request->validate([
 'name' => 'required|string',
 'api_endpoint' => 'required|url',
 'status' => 'required|in:active,inactive',
 ]);

 if (! AIService::providerIsSupported($request->name)) {
 return redirect()->back()
 ->withInput()
 ->with('error', 'This AI provider is not in the active provider pool. Use OpenAI, Gemini, Groq, or Cohere.');
 }

 $data = $request->only(['name', 'api_endpoint', 'status']);

 if ($request->filled('api_key')) {
 $data['api_key'] = Crypt::encryptString($request->api_key);
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

 public function setPrimaryProvider(AiProvider $provider)
 {
 if (! AIService::providerIsConfigured($provider->name) || $provider->status!== 'active' || empty($provider->api_key)) {
 return redirect()->back()->with('error', 'Only active providers with an API key can be set as primary.');
 }

 AiProvider::where('id', '!=', 0)->update(['is_primary' => false]);
 $provider->update(['is_primary' => true]);

 return redirect()->back()->with('message', 'Primary provider updated.');
 }

 public function setFallbackProvider(AiProvider $provider)
 {
 if (! AIService::providerIsConfigured($provider->name) || $provider->status!== 'active' || empty($provider->api_key)) {
 return redirect()->back()->with('error', 'Only active providers with an API key can be set as fallback.');
 }

 AiProvider::where('id', '!=', 0)->update(['is_fallback' => false]);
 $provider->update(['is_fallback' => true]);

 return redirect()->back()->with('message', 'Fallback provider updated.');
 }
}
