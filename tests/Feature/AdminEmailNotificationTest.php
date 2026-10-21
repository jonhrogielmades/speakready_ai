<?php

namespace Tests\Feature;

use App\Helpers\ActivityLogger;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminActivityAlert;
use App\Notifications\UserActivityNotification;
use App\Services\AdminActivityNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.admin_alerts.enabled' => true,
            'mail.admin_alerts.address' => 'capstonespeakreadyai@gmail.com',
            'mail.admin_alerts.name' => 'System Admin',
        ]);
    }

    public function test_important_activity_is_emailed_to_the_canonical_admin_inbox(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'legacy-admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'name' => 'New Learner',
            'email' => 'learner@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $activity = ActivityLogger::log(
            $user,
            'user_registered',
            'New Learner (learner@example.com) registered a new account.',
            '203.0.113.10',
            false
        );

        Notification::assertSentOnDemand(
            AdminActivityAlert::class,
            function (AdminActivityAlert $notification, array $channels, AnonymousNotifiable $notifiable) use ($activity): bool {
                $mail = $notification->toMail($notifiable);

                return $channels === ['mail']
                    && $notifiable->routes['mail'] === [
                        'capstonespeakreadyai@gmail.com' => 'System Admin',
                    ]
                    && $notification->activityId === $activity->id
                    && $notification->action === 'user_registered'
                    && $notification->actorIdentifier === 'learner@example.com'
                    && $notification->ipAddress === '203.0.113.10'
                    && $mail->subject === '[Admin Alert] User Registered | SpeakReady AI';
            }
        );
        Notification::assertSentOnDemandTimes(AdminActivityAlert::class, 1);
    }

    public function test_routine_activity_does_not_email_the_admin(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        ActivityLogger::log(
            $user,
            'learning_game_started',
            'A learner started a routine game level.',
            '203.0.113.11',
            false
        );

        Notification::assertNothingSent();
    }

    public function test_canonical_admin_does_not_receive_duplicate_mail_for_the_same_activity(): void
    {
        Notification::fake();
        Setting::setVal('notif_email', true, 'notifications', 'boolean');

        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'capstonespeakreadyai@gmail.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        ActivityLogger::log(
            $admin,
            'admin_password_changed',
            'System Admin changed their administrator account password.',
            '203.0.113.14',
            true
        );

        Notification::assertSentOnDemandTimes(AdminActivityAlert::class, 1);
        Notification::assertSentTo(
            $admin,
            UserActivityNotification::class,
            fn (UserActivityNotification $notification, array $channels): bool => $channels === ['database']
        );
    }

    public function test_admin_activity_alerts_can_be_disabled_without_disabling_the_audit_log(): void
    {
        Notification::fake();
        config(['mail.admin_alerts.enabled' => false]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $activity = ActivityLogger::log(
            $user,
            'reactivation_requested',
            'The user requested account reactivation.',
            '203.0.113.12',
            false
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $activity->id,
            'action' => 'reactivation_requested',
        ]);
        Notification::assertNothingSent();
    }

    public function test_admin_mail_failure_does_not_prevent_activity_logging(): void
    {
        $service = Mockery::mock(AdminActivityNotificationService::class);
        $service->shouldReceive('notify')
            ->once()
            ->andThrow(new RuntimeException('Mail transport unavailable.'));
        $this->app->instance(AdminActivityNotificationService::class, $service);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $activity = ActivityLogger::log(
            $user,
            'interview_completed',
            'The user completed an interview.',
            '203.0.113.13',
            false
        );

        $this->assertDatabaseHas('activity_logs', [
            'id' => $activity->id,
            'user_id' => $user->id,
            'action' => 'interview_completed',
        ]);
    }
}
