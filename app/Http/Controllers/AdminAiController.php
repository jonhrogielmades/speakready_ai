<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiProvider;
use App\Models\AiPrompt;
use App\Models\AiSetting;
use App\Models\AiProviderLog;
use Illuminate\Support\Facades\Crypt;

class AdminAiController extends Controller
{
    public function providers()
    {
        $activeProvider = AiProvider::where('is_primary', true)->first();
        $totalRequests = AiProviderLog::whereDate('created_at', today())->count();
        $successfulRequests = AiProviderLog::whereDate('created_at', today())->where('status', 'success')->count();
        $failedRequests = AiProviderLog::whereDate('created_at', today())->where('status', '!=', 'success')->count();
        
        $avgResponseTime = AiProviderLog::whereDate('created_at', today())->avg('response_time_ms') ?? 0;
        
        $successRate = $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 100;
        
        $monthlyCost = AiProviderLog::whereMonth('created_at', now()->month)->sum('cost') ?? 0;
        
        $moduleUsage = AiProviderLog::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->get();

        $providers = AiProvider::all()->map(function($provider) {
            $provider->requests_today = AiProviderLog::where('provider_id', $provider->id)->whereDate('created_at', today())->count();
            $provider->successful_requests = AiProviderLog::where('provider_id', $provider->id)->whereDate('created_at', today())->where('status', 'success')->count();
            $provider->avg_response_time = AiProviderLog::where('provider_id', $provider->id)->whereDate('created_at', today())->avg('response_time_ms') ?? 0;
            $provider->success_rate = $provider->requests_today > 0 ? round(($provider->successful_requests / $provider->requests_today) * 100, 2) : 100;
            $provider->monthly_cost = AiProviderLog::where('provider_id', $provider->id)->whereMonth('created_at', now()->month)->sum('cost') ?? 0;
            return $provider;
        });

        $primary = AiProvider::where('is_primary', true)->first();
        $fallback = AiProvider::where('is_fallback', true)->first();
        
        return view('admin.ai.providers', compact(
            'providers', 'primary', 'fallback',
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

        $provider = AiProvider::create([
            'name' => $request->name,
            'api_endpoint' => $request->api_endpoint,
            'api_key' => Crypt::encryptString($request->api_key),
            'status' => $request->status,
        ]);

        if (AiProvider::count() == 1) {
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
        AiProvider::where('id', '!=', 0)->update(['is_primary' => false]);
        $provider->update(['is_primary' => true]);
        return redirect()->back()->with('message', 'Primary provider updated.');
    }

    public function setFallbackProvider(AiProvider $provider)
    {
        AiProvider::where('id', '!=', 0)->update(['is_fallback' => false]);
        $provider->update(['is_fallback' => true]);
        return redirect()->back()->with('message', 'Fallback provider updated.');
    }

}
