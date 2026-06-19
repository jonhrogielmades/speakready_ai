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
    public function dashboard()
    {
        $activeProvider = AiProvider::where('is_primary', true)->first();
        $totalRequests = AiProviderLog::whereDate('created_at', today())->count();
        $successfulRequests = AiProviderLog::whereDate('created_at', today())->where('status', 'success')->count();
        $failedRequests = AiProviderLog::whereDate('created_at', today())->where('status', '!=', 'success')->count();
        
        $avgResponseTime = AiProviderLog::whereDate('created_at', today())->avg('response_time_ms') ?? 0;
        
        $successRate = $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 100;
        
        // Mock data for charts
        $monthlyCost = AiProviderLog::whereMonth('created_at', now()->month)->sum('cost') ?? 0;
        
        $moduleUsage = AiProviderLog::selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->get();

        return view('admin.ai.dashboard', compact(
            'activeProvider', 'totalRequests', 'successfulRequests', 'failedRequests', 
            'avgResponseTime', 'successRate', 'monthlyCost', 'moduleUsage'
        ));
    }

    public function providers()
    {
        $providers = AiProvider::all();
        $primary = AiProvider::where('is_primary', true)->first();
        $fallback = AiProvider::where('is_fallback', true)->first();
        
        return view('admin.ai.providers', compact('providers', 'primary', 'fallback'));
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

    public function prompts()
    {
        $prompts = AiPrompt::all();
        return view('admin.ai.prompts', compact('prompts'));
    }

    public function storePrompt(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'module' => 'required|string',
            'prompt_text' => 'required|string',
        ]);

        AiPrompt::updateOrCreate(
            ['module' => $request->module],
            ['name' => $request->name, 'prompt_text' => $request->prompt_text]
        );

        return redirect()->back()->with('message', 'Prompt updated successfully.');
    }

    public function settings()
    {
        $settings = AiSetting::pluck('value', 'key')->toArray();
        $providers = AiProvider::all();
        return view('admin.ai.settings', compact('settings', 'providers'));
    }

    public function storeSettings(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            AiSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return redirect()->back()->with('message', 'Settings saved successfully.');
    }

    public function testing()
    {
        $providers = AiProvider::all();
        return view('admin.ai.testing', compact('providers'));
    }

    public function testAiResponse(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'provider_id' => 'required|exists:ai_providers,id'
        ]);

        // Simulating AI Response for testing purposes
        $response = "This is a simulated AI response from the selected provider for prompt: " . $request->prompt;
        
        return response()->json([
            'success' => true,
            'response' => $response,
            'time_ms' => rand(500, 2500)
        ]);
    }

    public function logs()
    {
        $logs = AiProviderLog::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.ai.logs', compact('logs'));
    }
}
