<?php

namespace Tests\Unit;

use App\Services\EvidenceBasedCoachingService;
use PHPUnit\Framework\TestCase;

class CameraObservationReliabilityTest extends TestCase
{
    public function test_valid_turning_and_off_center_observations_do_not_lower_signal_reliability(): void
    {
        $camera = $this->camera($this->samples([
            'camera_facing' => false,
            'camera_facing_score' => 25,
            'centered' => false,
            'framing_quality' => false,
            'shoulders_level' => false,
            'upright_posture' => false,
        ]));

        $this->assertSame('measured', $camera['status']);
        $this->assertTrue($camera['high_confidence']);
        $this->assertSame(0, $camera['camera_facing_percent']);
        $this->assertSame(0, $camera['centered_percent']);
        $this->assertSame(0, $camera['shoulders_level_percent']);
        $this->assertSame(0, $camera['upright_posture_percent']);
        $this->assertStringContainsString('not a measured accuracy percentage', $camera['reliability_basis']);
        $this->assertStringContainsString('No sideways head turn was observed in 0%', $this->feedback($camera)['observation']);
        $this->assertStringNotContainsString('looked camera-facing', $this->feedback($camera)['observation']);
    }

    public function test_face_only_frames_do_not_invent_torso_or_movement_results(): void
    {
        $camera = $this->camera($this->samples([
            'pose_detected' => false,
            'pose_count' => 0,
            'pose_confidence' => null,
            'torso_visible' => false,
            'shoulders_visible' => false,
            'movement_score' => 90,
        ]));

        $this->assertSame('measured', $camera['status']);
        $this->assertNull($camera['shoulders_level_percent']);
        $this->assertNull($camera['upright_posture_percent']);
        $this->assertNull($camera['average_movement_score']);
        $this->assertSame(0, $camera['upright_posture_measured_count']);
    }

    public function test_cropped_hips_and_unknown_orientation_remain_unmeasured(): void
    {
        $camera = $this->camera($this->samples([
            'torso_visible' => false,
            'camera_facing' => null,
            'camera_facing_score' => null,
            'centered' => null,
            'framing_quality' => null,
        ]));

        $this->assertSame('measured', $camera['status']);
        $this->assertNull($camera['upright_posture_percent']);
        $this->assertNull($camera['camera_facing_percent']);
        $this->assertNull($camera['centered_percent']);
        $this->assertNull($camera['framing_quality_percent']);
        $feedback = $this->feedback($camera);
        $this->assertStringContainsString('Head orientation was not measured', $feedback['observation']);
        $this->assertStringNotContainsString('upright', $feedback['observation']);
        $this->assertStringNotContainsString('camera-facing 0%', $feedback['observation']);
    }

    public function test_partial_orientation_samples_use_only_measured_frames_as_the_denominator(): void
    {
        $samples = $this->samples(['camera_facing' => null, 'camera_facing_score' => null]);
        $samples[0]['camera_facing'] = true;
        $samples[0]['camera_facing_score'] = 90;
        $samples[1]['camera_facing'] = false;
        $samples[1]['camera_facing_score'] = 20;
        $camera = $this->camera($samples);

        $this->assertSame(2, $camera['camera_facing_measured_count']);
        $this->assertSame(50, $camera['camera_facing_percent']);
    }

    public function test_low_quality_stale_multiple_person_and_calibrating_frames_do_not_become_behavior_evidence(): void
    {
        foreach ([
            ['sample_quality' => 30],
            ['detection_age_ms' => 1501],
            ['face_count' => 2],
            ['pose_count' => 2],
            ['observation_status' => 'calibrating'],
            ['observation_status' => 'low_confidence'],
        ] as $changes) {
            $camera = $this->camera($this->samples($changes));

            $this->assertSame('insufficient_data', $camera['status'], json_encode($changes));
            $this->assertFalse($camera['high_confidence']);
            $this->assertSame(0, $camera['usable_sample_count']);
            $this->assertSame(6, $camera['rejected_sample_count']);
            $this->assertNull($camera['shoulders_level_percent']);
            $this->assertNull($camera['upright_posture_percent']);
            $this->assertNull($camera['high_movement_percent']);
            $this->assertStringContainsString('no camera behavior conclusion', $this->feedback($camera)['observation']);
        }
    }

    public function test_rejected_frames_do_not_dilute_or_improve_valid_behavior_percentages(): void
    {
        $samples = $this->samples(['shoulders_level' => false, 'upright_posture' => false]);
        $bad = $this->samples()[5];
        $bad['sample_index'] = 7;
        $bad['at_seconds'] = 12;
        $bad['captured_at_ms'] = 13000;
        $bad['frame_time_ms'] = 13000;
        $bad['sample_quality'] = 20;
        $samples[] = $bad;
        $camera = $this->camera($samples);

        $this->assertSame('measured', $camera['status']);
        $this->assertSame(6, $camera['usable_sample_count']);
        $this->assertSame(1, $camera['rejected_sample_count']);
        $this->assertSame(0, $camera['shoulders_level_percent']);
        $this->assertSame(0, $camera['upright_posture_percent']);
    }

    public function test_duplicates_frozen_video_and_out_of_order_frames_cannot_satisfy_the_sample_gate(): void
    {
        foreach (['sample_index', 'captured_at_ms', 'frame_time_ms'] as $field) {
            $samples = $this->samples();
            foreach ($samples as &$sample) {
                $sample[$field] = $samples[0][$field];
            }
            unset($sample);
            $camera = $this->camera($samples);

            $this->assertSame('insufficient_data', $camera['status'], $field);
            $this->assertSame(1, $camera['usable_sample_count']);
            $this->assertSame(5, $camera['rejected_sample_reasons']['duplicate_frame']);
        }

        $samples = $this->samples();
        $samples[5]['frame_time_ms'] = 500;
        $camera = $this->camera($samples);
        $this->assertSame('insufficient_data', $camera['status']);
        $this->assertSame(1, $camera['rejected_sample_reasons']['out_of_order_frame']);
    }

    public function test_sparse_endpoints_cannot_claim_coverage_of_a_long_answer(): void
    {
        $samples = $this->samples();
        $samples[5]['at_seconds'] = 120;
        $samples[5]['captured_at_ms'] = 121000;
        $samples[5]['frame_time_ms'] = 121000;
        $camera = $this->camera($samples, 120);

        $this->assertSame(120, $camera['sampling_span_seconds']);
        $this->assertSame(12, $camera['covered_seconds']);
        $this->assertSame(10, $camera['sampling_coverage_percent']);
        $this->assertSame('insufficient_data', $camera['status']);
        $this->assertFalse($camera['reliability_checks']['continuous_time_coverage']);
        $this->assertNotSame('High', $camera['reliability_band']);
    }

    public function test_malformed_numbers_and_metadata_are_rejected_without_being_clamped_into_valid_evidence(): void
    {
        foreach ([
            ['face_confidence' => 1000],
            ['pose_confidence' => INF],
            ['sample_quality' => NAN],
            ['sample_quality' => '90 points'],
            ['at_seconds' => -1],
            ['at_seconds' => 23],
            ['face_count' => 1.4],
            ['camera_facing' => ['true']],
            ['frame_time_ms' => null],
            ['observation_status' => 'invented'],
        ] as $changes) {
            $camera = $this->camera($this->samples($changes));
            $this->assertSame('insufficient_data', $camera['status']);
            $this->assertSame(0, $camera['sample_count']);
            $this->assertSame(6, $camera['rejected_sample_count']);
            $this->assertFalse($camera['high_confidence']);
        }
    }

    public function test_pose_confidence_and_torso_visibility_are_required_for_posture_evidence(): void
    {
        $camera = $this->camera($this->samples(['pose_confidence' => 64]));

        $this->assertSame('measured', $camera['status']);
        $this->assertSame(0, $camera['pose_detected_count']);
        $this->assertNull($camera['shoulders_level_percent']);
        $this->assertNull($camera['upright_posture_percent']);
        $this->assertNull($camera['average_movement_score']);
    }

    public function test_an_unavailable_detector_does_not_discard_the_other_valid_modality(): void
    {
        $camera = $this->camera($this->samples([
            'face_count' => null,
            'face_detected' => false,
            'face_confidence' => null,
        ]));
        $this->assertSame('measured', $camera['status']);
        $this->assertSame(6, $camera['pose_detected_count']);
        $this->assertNull($camera['face_visibility_percent']);
        $this->assertSame(0, $camera['face_visibility_measured_count']);
        $this->assertNull($camera['camera_facing_percent']);
        $this->assertNull($camera['samples'][0]['face_count']);
        $this->assertStringContainsString('Face visibility was not measured', $this->feedback($camera)['observation']);

        $camera = $this->camera($this->samples([
            'pose_count' => null,
            'pose_detected' => false,
            'pose_confidence' => null,
        ]));
        $this->assertSame('measured', $camera['status']);
        $this->assertSame(6, $camera['detection_count']);
        $this->assertNull($camera['upright_posture_percent']);
        $this->assertSame(0, $camera['pose_detection_measured_count']);
        $this->assertNull($camera['samples'][0]['pose_count']);
    }

    public function test_reported_quality_cannot_override_weak_landmark_confidence(): void
    {
        $camera = $this->camera($this->samples([
            'face_count' => null,
            'face_detected' => false,
            'face_confidence' => null,
            'pose_confidence' => 65,
            'sample_quality' => 100,
        ]));

        $this->assertSame('insufficient_data', $camera['status']);
        $this->assertSame(65, $camera['average_sample_quality']);
        $this->assertFalse($camera['reliability_checks']['sample_quality']);
        $this->assertFalse($camera['high_confidence']);
    }

    public function test_restarted_capture_has_an_independent_sequence_and_media_clock(): void
    {
        $first = array_slice($this->samples(['capture_session_id' => 'capture-first']), 0, 3);
        $resumed = array_slice($this->samples(['capture_session_id' => 'capture-resumed']), 0, 3);
        foreach ($resumed as &$sample) {
            $sample['at_seconds'] += 6;
        }
        unset($sample);
        $camera = $this->camera(array_merge($first, $resumed));

        $this->assertSame('measured', $camera['status']);
        $this->assertSame(6, $camera['usable_sample_count']);
        $this->assertSame(0, $camera['rejected_sample_count']);
    }

    public function test_camera_window_can_use_text_elapsed_time_without_fabricating_voice_metrics(): void
    {
        $observations = (new EvidenceBasedCoachingService)->normalizeObservationData([
            'camera_detection_protocol' => 3,
            'camera_samples' => $this->samples(),
        ], 'I explained the issue and verified the result.', [
            'response_mode' => 'text',
            'voice_duration' => 0,
            'elapsed_seconds' => 20,
        ], true);

        $this->assertSame('measured', $observations['camera']['status']);
        $this->assertSame('not_measured', $observations['delivery']['status']);
        $this->assertNull($observations['delivery']['duration_seconds']);
    }

    public function test_camera_end_tolerates_small_clock_drift_but_bounds_saved_timestamps(): void
    {
        $samples = $this->samples();
        $samples[5]['at_seconds'] = 21;
        $camera = $this->camera($samples);

        $this->assertSame('measured', $camera['status']);
        $this->assertSame(20, $camera['samples'][5]['at_seconds']);
        $this->assertSame(0, $camera['rejected_sample_count']);
    }

    public function test_round_trip_preserves_protocol_capture_namespaces_and_unavailable_dimensions(): void
    {
        $service = new EvidenceBasedCoachingService;
        $metrics = ['response_mode' => 'voice', 'voice_duration' => 20];
        $previous = $service->normalizeObservationData([
            'camera_detection_protocol' => 3,
            'camera_samples' => $this->samples([
                'capture_session_id' => 'capture-roundtrip',
                'pose_count' => null,
                'pose_detected' => false,
                'pose_confidence' => null,
                'camera_facing' => null,
                'camera_facing_score' => null,
            ]),
        ], 'I explained the issue and verified the result.', $metrics, true);

        $renormalized = $service->normalizeObservationData($previous, 'I explained the issue and verified the result.', $metrics, true);

        $this->assertSame(3, $renormalized['camera']['camera_detection_protocol']);
        $this->assertSame('measured', $renormalized['camera']['status']);
        $this->assertSame($previous['camera'], $renormalized['camera']);
        $this->assertNull($renormalized['camera']['samples'][0]['pose_count']);
        $this->assertSame('capture-roundtrip', $renormalized['camera']['samples'][0]['capture_session_id']);
    }

    public function test_round_trip_does_not_erase_discarded_frames_and_improve_quality(): void
    {
        $service = new EvidenceBasedCoachingService;
        $metrics = ['response_mode' => 'voice', 'voice_duration' => 20];
        $samples = $this->samples(['capture_session_id' => 'capture-roundtrip']);
        $previous = $service->normalizeObservationData([
            'camera_detection_protocol' => 3,
            'camera_samples' => array_merge($samples, $samples, [['sample_quality' => 1000]]),
        ], 'I explained the issue and verified the result.', $metrics, true);
        $renormalized = $service->normalizeObservationData($previous, 'I explained the issue and verified the result.', $metrics, true);

        $this->assertSame('insufficient_data', $previous['camera']['status']);
        $this->assertSame(6, $renormalized['camera']['sample_count']);
        $this->assertSame(13, $renormalized['camera']['received_sample_count']);
        $this->assertSame(7, $renormalized['camera']['rejected_sample_count']);
        $this->assertFalse($renormalized['camera']['reliability_checks']['usable_frame_coverage']);
        $this->assertSame($previous['camera'], $renormalized['camera']);

        $rawPayload = [
            'camera_detection_protocol' => 3,
            'camera_samples' => $samples,
            'camera' => ['received_sample_count' => 300],
        ];
        $fresh = $service->normalizeObservationData($rawPayload, 'I explained the issue and verified the result.', $metrics, true);
        $this->assertSame(6, $fresh['camera']['received_sample_count']);
        $this->assertSame('measured', $fresh['camera']['status']);
    }

    public function test_movement_boolean_is_recomputed_and_face_size_category_cannot_contradict_measurement(): void
    {
        $camera = $this->camera($this->samples([
            'movement_score' => 10,
            'high_movement' => true,
            'face_size_percent' => 1,
            'face_size_status' => 'good',
        ]));

        $this->assertSame(0, $camera['high_movement_percent']);
        $this->assertSame(6, $camera['face_size_too_far_count']);
        $this->assertSame(0, $camera['framing_quality_percent']);
    }

    private function samples(array $changes = []): array
    {
        $samples = [];
        for ($index = 0; $index < 6; $index++) {
            $samples[] = array_replace([
                'sample_index' => $index + 1,
                'at_seconds' => $index * 2,
                'captured_at_ms' => 1000 + $index * 2000,
                'frame_time_ms' => 1000 + $index * 2000,
                'detection_age_ms' => 50,
                'observation_status' => 'measured',
                'sample_quality' => 90,
                'face_detected' => true,
                'face_count' => 1,
                'face_confidence' => 90,
                'face_size_percent' => 10,
                'face_size_status' => 'good',
                'camera_facing' => true,
                'camera_facing_score' => 90,
                'centered' => true,
                'framing_quality' => true,
                'pose_detected' => true,
                'pose_count' => 1,
                'pose_confidence' => 90,
                'shoulders_visible' => true,
                'shoulders_level' => true,
                'torso_visible' => true,
                'upright_posture' => true,
                'movement_score' => 10,
                'high_movement' => false,
            ], $changes);
        }

        return $samples;
    }

    private function camera(array $samples, int $duration = 20): array
    {
        return (new EvidenceBasedCoachingService)->normalizeObservationData([
            'camera_detection_protocol' => 3,
            'camera_samples' => $samples,
        ], 'I explained the issue and verified the result with the team.', [
            'response_mode' => 'voice',
            'voice_duration' => $duration,
        ], true)['camera'];
    }

    private function feedback(array $camera): array
    {
        return (new EvidenceBasedCoachingService)->forAnswer(
            'I explained the issue and verified the result with the team.',
            ['type' => 'Personal', 'question_text' => 'Tell me about yourself.'],
            ['response_mode' => 'voice', 'voice_duration' => 20],
            ['delivery' => ['status' => 'not_measured'], 'camera' => $camera]
        )['camera_feedback'];
    }
}
