<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserSessionRevoker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserSessionRevokerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_other_database_sessions_and_all_api_tokens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        DB::table('sessions')->insert([
            $this->sessionRow('current-session', $user->id),
            $this->sessionRow('other-session', $user->id),
            $this->sessionRow('unrelated-session', $other->id),
        ]);

        $user->createToken('test-device');

        app(UserSessionRevoker::class)->revoke($user, 'current-session');

        $this->assertDatabaseHas('sessions', ['id' => 'current-session', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated-session', 'user_id' => $other->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    /** @return array<string, mixed> */
    private function sessionRow(string $id, int $userId): array
    {
        return [
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test'),
            'last_activity' => time(),
        ];
    }
}
