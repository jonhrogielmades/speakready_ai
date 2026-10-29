<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RemovedOfflineApplicationFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_application_and_pack_endpoints_stay_unavailable(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        foreach ([
            'user.applications.store',
            'user.applications.update',
            'user.applications.destroy',
            'user.applications.practice',
            'user.packs.practice',
        ] as $routeName) {
            $this->assertNull(Route::getRoutes()->getByName($routeName));
        }

        $this->actingAs($user)->post('/offline-applications')->assertNotFound();
        $this->actingAs($user)->put('/offline-applications/1')->assertNotFound();
        $this->actingAs($user)->delete('/offline-applications/1')->assertNotFound();
        $this->actingAs($user)->get('/offline-applications/1/practice')->assertNotFound();
        $this->actingAs($user)->get('/offline-packs/1/practice')->assertNotFound();
    }
}
