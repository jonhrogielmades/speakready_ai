<?php

namespace Tests\Feature;

use App\Support\AiProviderSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiProviderSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_provider_schema_repair_creates_missing_tables_for_guest_homepage(): void
    {
        Schema::dropIfExists('ai_provider_logs');
        Schema::dropIfExists('ai_providers');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_settings');

        AiProviderSchema::ensure(force: true, createIfMissing: true);

        $this->assertTrue(Schema::hasTable('ai_providers'));
        $this->assertTrue(Schema::hasColumn('ai_providers', 'is_primary'));
        $this->assertTrue(Schema::hasTable('ai_provider_logs'));
        $this->assertTrue(Schema::hasColumn('ai_provider_logs', 'response_time_ms'));
        $this->assertTrue(Schema::hasTable('ai_prompts'));
        $this->assertTrue(Schema::hasTable('ai_settings'));

        $this->get('/')->assertOk();
    }
}
