<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiProvider;
use App\Models\AiPrompt;
use App\Models\AiSetting;
use App\Models\AiProviderLog;
use App\Services\AIService;
use App\Support\AiProviderSchema;
use Illuminate\Support\Facades\Crypt;

class AdminAiController extends Controller
{
    public function providers()
    {
        AiProviderSchema::ensure(force: true, createIfMissing: true);

        $activeProvider = AiProvider::safePrimaryOrActive();
        $totalRequests = AiProviderLog::whereDate('created_at', today())->count();
        $successfulRequests = AiProviderLog::whereDate('created_at', today())->where('status', 'success')->count();
        $failedRequests = AiProviderLog::whereDate('created_at', today())->where('status', '!=', 'success')->count();
        
        $avgResponseTime = AiProviderLog::whereDate('created_at', today())->avg('response_time_ms') ?? 0;
        
        $successRate = $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 100;
        
        $monthlyCost = AiProviderLog::whereMonth('created_at', now()->month)->sum('cost') ?? 0;
        
        $moduleUsage = AiProviderLog::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->get();

        $providers = AiProvider::all(); // For the table below
        $defaultProviders = collect(AIService::supportedProviderOptions());
        
        $providerStats = $defaultProviders->map(function(array $providerOption) use ($providers) {
            $dbProvider = $providers->first(
                fn (AiProvider $provider): bool => AIService::normalizeProviderKey($provider->name) === $providerOption['key']
            );
            $stats = new \stdClass();
            $stats->name = $providerOption['label'];
            
            if ($dbProvider) {
                $stats->is_configured = true;
                $stats->status = $dbProvider->status;
                $stats->is_primary = $dbProvider->is_primary;
                $stats->is_fallback = $dbProvider->is_fallback;
                $stats->requests_today = AiProviderLog::where('provider_id', $dbProvider->id)->whereDate('created_at', today())->count();
                $stats->successful_requests = AiProviderLog::where('provider_id', $dbProvider->id)->whereDate('created_at', today())->where('status', 'success')->count();
                $stats->avg_response_time = AiProviderLog::where('provider_id', $dbProvider->id)->whereDate('created_at', today())->avg('response_time_ms') ?? 0;
                $stats->success_rate = $stats->requests_today > 0 ? round(($stats->successful_requests / $stats->requests_today) * 100, 2) : 100;
                $stats->monthly_cost = AiProviderLog::where('provider_id', $dbProvider->id)->whereMonth('created_at', now()->month)->sum('cost') ?? 0;
            } else {
                $stats->is_configured = $providerOption['enabled'];
                $stats->status = $providerOption['enabled'] ? 'active' : 'unconfigured';
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

        $primary = AiProvider::where('is_primary', true)
            ->where('status', 'active')
            ->whereNotNull('api_key')
            ->first();
        $fallback = AiProvider::where('is_fallback', true)->first();
        
        return view('admin.ai.providers', compact(
            'providers', 'providerStats', 'primary', 'fallback',
            'activeProvider', 'totalRequests', 'successfulRequests', 'failedRequests', 
            'avgResponseTime', 'successRate', 'monthlyCost', 'moduleUsage'
        ));
    }

    public function storeProvider(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'api_endpoint' => 'required|url',
            'api_key' => 'required|string',
            'status' => 'required|in:active,inactive'
        ]);

        if (! AIService::providerIsSupported($request->name)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This AI provider is not in the active provider pool. Use OpenAI, Gemini, Claude, Groq, OpenRouter, WisdomGate, Cohere, or Hugging Face.');
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
            'status' => 'required|in:active,inactive'
        ]);

        if (! AIService::providerIsSupported($request->name)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This AI provider is not in the active provider pool. Use OpenAI, Gemini, Claude, Groq, OpenRouter, WisdomGate, Cohere, or Hugging Face.');
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
        if (! AIService::providerIsConfigured($provider->name) || $provider->status !== 'active' || empty($provider->api_key)) {
            return redirect()->back()->with('error', 'Only active providers with an API key can be set as primary.');
        }

        AiProvider::where('id', '!=', 0)->update(['is_primary' => false]);
        $provider->update(['is_primary' => true]);
        return redirect()->back()->with('message', 'Primary provider updated.');
    }

    public function setFallbackProvider(AiProvider $provider)
    {
        if (! AIService::providerIsConfigured($provider->name) || $provider->status !== 'active' || empty($provider->api_key)) {
            return redirect()->back()->with('error', 'Only active providers with an API key can be set as fallback.');
        }

        AiProvider::where('id', '!=', 0)->update(['is_fallback' => false]);
        $provider->update(['is_fallback' => true]);
        return redirect()->back()->with('message', 'Fallback provider updated.');
    }

}
