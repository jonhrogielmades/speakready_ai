<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewPack;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_tracker_practice_launch_prefills_and_links_interview_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Acme Labs',
            'job_title' => 'Backend Developer',
            'status' => 'tracking',
            'resume_text' => 'Laravel PHP API testing experience.',
            'job_description' => 'Backend developer role using Laravel APIs and automated testing.',
            'match_score' => 80,
            'matched_keywords' => ['laravel', 'testing'],
            'missing_keywords' => ['queue'],
        ]);

        $this->actingAs($user)
            ->get(route('user.applications.practice', $application))
            ->assertRedirect(route('interview.setup', ['application' => $application->id]));

        $this->actingAs($user)
            ->get(route('interview.setup', ['application' => $application->id]))
            ->assertOk()
            ->assertSee('name="job_application_id" value="'.$application->id.'"', false)
            ->assertSee('value="Backend Developer"', false)
            ->assertSee('Laravel PHP API testing experience.', false);

        $this->actingAs($user)
            ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
                'job_application_id' => $application->id,
                'target_position' => 'Backend Developer',
            ]))
            ->assertRedirect(route('interview.session'));

        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $user->id,
            'job_application_id' => $application->id,
            'target_position' => 'Backend Developer',
            'resume_text' => 'Laravel PHP API testing experience.',
            'job_description' => 'Backend developer role using Laravel APIs and automated testing.',
            'status' => 'in_progress',
        ]);
    }

    public function test_pack_launcher_prefills_and_links_pressure_mode_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->category(['title' => 'Job Interview', 'sort_order' => 1]);
        $category = $this->category(['title' => 'IT/Programming', 'sort_order' => 2]);
        $pack = InterviewPack::create([
            'name' => 'Google Product/Technical Screen',
            'slug' => 'google-product-technical-screen-regression',
            'company' => 'Google',
            'role_family' => 'Technical',
            'difficulty' => 'hard',
            'interview_focus' => 'Problem Solving',
            'company_persona' => 'Google',
            'question_types' => ['Technical', 'Situational'],
            'sample_questions' => ['Walk me through a debugging process.'],
            'description' => 'Technical screen practice.',
            'pressure_mode' => true,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('user.packs.practice', $pack))
            ->assertRedirect(route('interview.setup', ['pack' => $pack->id]));

        $this->actingAs($user)
            ->get(route('interview.setup', ['pack' => $pack->id]))
            ->assertOk()
            ->assertSee('name="interview_pack_id" value="'.$pack->id.'"', false)
            ->assertSee('value="'.$category->id.'"', false)
            ->assertSee('value="Technical Role"', false)
            ->assertSee('value="hard"', false)
            ->assertSee('value="real_interview" selected', false)
            ->assertSee('value="Technical" checked', false);

        $this->actingAs($user)
            ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
                'interview_pack_id' => $pack->id,
                'difficulty' => 'hard',
                'target_position' => 'Technical Role',
                'question_types' => ['Technical', 'Situational'],
                'interview_focus' => 'Problem Solving',
                'company_persona' => 'Google',
                'live_feedback_mode' => 'real_interview',
                'time_limit' => 2,
            ]))
            ->assertRedirect(route('interview.session'));

        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $user->id,
            'interview_pack_id' => $pack->id,
            'difficulty' => 'hard',
            'target_position' => 'Technical Role',
            'pressure_mode' => true,
            'live_feedback_mode' => 'real_interview',
            'company_persona' => 'Philippines hiring context - Google',
            'status' => 'in_progress',
        ]);

        $session = InterviewSession::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(['Technical', 'Situational'], json_decode($session->question_types, true));
    }

    public function test_application_tracker_crud_and_plan_toggle_are_authorized(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.applications.store'), [
                'company_name' => 'Acme Labs',
                'job_title' => 'Backend Developer',
                'status' => 'tracking',
                'resume_text' => 'Laravel testing APIs',
                'job_description' => 'Laravel APIs testing queues observability',
            ])
            ->assertRedirect(route('user.applications.index'));

        $application = JobApplication::where('user_id', $user->id)->firstOrFail();
        $this->assertGreaterThan(0, $application->match_score);
        $this->assertSame(7, $application->planItems()->count());

        $this->actingAs($user)
            ->put(route('user.applications.update', $application), [
                'company_name' => 'Acme Labs',
                'job_title' => 'Senior Backend Developer',
                'status' => 'interviewing',
                'interview_stage' => 'Final Panel',
                'resume_text' => 'Laravel testing APIs queues',
                'job_description' => 'Laravel APIs testing queues observability',
            ])
            ->assertRedirect(route('user.applications.index'));

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'job_title' => 'Senior Backend Developer',
            'status' => 'interviewing',
            'interview_stage' => 'Final Panel',
        ]);

        $planItem = $application->planItems()->firstOrFail();
        $this->actingAs($otherUser)
            ->postJson(route('user.practice-plan.toggle', $planItem))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('user.practice-plan.toggle', $planItem))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('completed', true);

        $this->assertNotNull($planItem->refresh()->completed_at);

        $this->actingAs($otherUser)
            ->delete(route('user.applications.destroy', $application))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('user.applications.destroy', $application))
            ->assertRedirect(route('user.applications.index'));

        $this->assertDatabaseMissing('job_applications', ['id' => $application->id]);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Job Interview',
            'description' => 'General interview practice',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function interviewPayload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 5,
            'response_mode' => 'text',
            'time_limit' => 0,
            'ai_provider' => 'local',
        ];
    }
}
