<?php

namespace App\Support;

final class InterviewerAvatar
{
    public const DEFAULT = 'friendly_recruiter';

    private const OPTIONS = [
        'friendly_recruiter' => [
            'label' => 'Friendly Recruiter',
            'image' => 'img/interviewers/Filipina_Interviewer_06.webp',
            'background' => 'img/interviewer-panel-room-bg.webp',
            'opening' => 'Welcome the candidate in a relaxed, reassuring way while remaining professional.',
            'follow_up' => 'Use an inviting, supportive tone and help the candidate expand on a real example.',
        ],
        'structured_hr' => [
            'label' => 'Structured HR',
            'image' => 'img/interviewers/Filipina_Interviewer_01.webp',
            'background' => 'img/interviewers/structured_hr_room.webp',
            'opening' => 'Greet the candidate clearly and professionally, with a concise and orderly transition into the introduction.',
            'follow_up' => 'Ask a clear, focused question about the candidate\'s own actions and evidence. Keep the flow organized.',
        ],
        'hiring_manager' => [
            'label' => 'Hiring Manager',
            'image' => 'img/interviewers/Filipina_Interviewer_02.webp',
            'background' => 'img/interviewers/hiring_manager_room.webp',
            'opening' => 'Sound practical and interested in how the candidate would contribute to the role.',
            'follow_up' => 'Probe practical ownership, delivery, collaboration, and results in the target role.',
        ],
        'role_specialist' => [
            'label' => 'Role Specialist',
            'image' => 'img/interviewers/Filipina_Interviewer_03.webp',
            'background' => 'img/interviewers/role_specialist_room.webp',
            'opening' => 'Sound curious about the candidate\'s role-specific experience, without adding another question.',
            'follow_up' => 'Probe the role-specific method, tools, reasoning, or verification behind the candidate\'s answer.',
        ],
        'senior_leader' => [
            'label' => 'Senior Leader',
            'image' => 'img/interviewers/Filipina_Interviewer_04.webp',
            'background' => 'img/interviewers/senior_leader_room.webp',
            'opening' => 'Use a concise, composed executive tone and invite the candidate to connect their background to the role.',
            'follow_up' => 'Probe judgment, tradeoffs, stakeholder impact, and the significance of the candidate\'s result.',
        ],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::OPTIONS);
    }

    public static function normalize(mixed $key): string
    {
        return is_string($key) && isset(self::OPTIONS[$key]) ? $key : self::DEFAULT;
    }

    public static function label(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['label'];
    }

    public static function image(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['image'];
    }

    public static function background(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['background'];
    }

    public static function openingTone(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['opening'];
    }

    public static function followUpInstruction(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['follow_up'];
    }
}
