<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RemovedPersonalMasteryFeatureTest extends TestCase
{
    public function test_personal_mastery_routes_are_not_exposed(): void
    {
        $this->assertFalse(Route::has('user.mastery'));
        $this->assertFalse(Route::has('user.mastery.stories.store'));
        $this->assertFalse(Route::has('user.mastery.stories.destroy'));
        $this->assertFalse(Route::has('user.mastery.checklist.toggle'));

        $this->get('/personal-mastery')->assertNotFound();
        $this->post('/personal-mastery/stories')->assertNotFound();
        $this->delete('/personal-mastery/stories/1')->assertNotFound();
        $this->post('/personal-mastery/checklist/1/toggle')->assertNotFound();
    }
}
