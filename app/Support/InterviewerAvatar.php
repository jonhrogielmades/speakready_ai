<?php

namespace App\Support;

final class InterviewerAvatar
{
    public const DEFAULT = 'friendly_recruiter';

    private const OPTIONS = [
        'friendly_recruiter' => [
            'label' => 'Friendly Recruiter',
            'image' => 'img/interviewers/friendly_recruiter_portrait.webp',
            'background' => 'img/interviewer-panel-room-bg.webp',
            'gender' => 'woman',
            'voice' => 'Sulafat',
            'voice_style' => 'Speak warmly and confidently as a professional woman interviewer, with a calm, welcoming pace',
            'opening' => 'Welcome the candidate in a relaxed, reassuring way while remaining professional.',
            'follow_up' => 'Use an inviting, supportive tone and help the candidate expand on a real example.',
        ],
        'structured_hr' => [
            'label' => 'Structured HR',
            'image' => 'img/interviewers/structured_hr_portrait.webp',
            'background' => 'img/interviewers/structured_hr_room.webp',
            'gender' => 'woman',
            'voice' => 'Kore',
            'voice_style' => 'Speak firmly and clearly as a professional woman interviewer, with precise, measured pacing',
            'opening' => 'Greet the candidate clearly and professionally, with a concise and orderly transition into the introduction.',
            'follow_up' => 'Ask a clear, focused question about the candidate\'s own actions and evidence. Keep the flow organized.',
        ],
        'hiring_manager' => [
            'label' => 'Hiring Manager',
            'image' => 'img/interviewers/hiring_manager_portrait.webp',
            'background' => 'img/interviewers/hiring_manager_room.webp',
            'gender' => 'man',
            'voice' => 'Orus',
            'voice_style' => 'Speak with practical, assured authority as a professional man interviewer, at a measured pace',
            'opening' => 'Sound practical and interested in how the candidate would contribute to the role.',
            'follow_up' => 'Probe practical ownership, delivery, collaboration, and results in the target role.',
        ],
        'role_specialist' => [
            'label' => 'Role Specialist',
            'image' => 'img/interviewers/role_specialist_portrait.webp',
            'background' => 'img/interviewers/role_specialist_room.webp',
            'gender' => 'man',
            'voice' => 'Iapetus',
            'voice_style' => 'Speak with crisp, analytical clarity as a professional man interviewer, at a focused pace',
            'opening' => 'Sound curious about the candidate\'s role-specific experience, without adding another question.',
            'follow_up' => 'Probe the role-specific method, tools, reasoning, or verification behind the candidate\'s answer.',
        ],
        'senior_leader' => [
            'label' => 'Senior Leader',
            'image' => 'img/interviewers/senior_leader_portrait.webp',
            'background' => 'img/interviewers/senior_leader_room.webp',
            'gender' => 'woman',
            'voice' => 'Gacrux',
            'voice_style' => 'Speak with composed, mature authority as a professional woman interviewer, at a concise, unhurried pace',
            'opening' => 'Use a concise, composed executive tone and invite the candidate to connect their background to the role.',
            'follow_up' => 'Probe judgment, tradeoffs, stakeholder impact, and the significance of the candidate\'s result.',
        ],
        'panel_interviewer' => [
            'label' => 'Panel Interviewer',
            'image' => 'img/interviewers/panel_interviewer_portrait.webp',
            'background' => 'img/interviewers/panel_interviewer_room.webp',
            'gender' => 'man',
            'voice' => 'Algenib',
            'voice_style' => 'Speak with a challenging yet respectful gravelly tone as a professional man interviewer, at a controlled pace',
            'opening' => 'Greet the candidate with calm authority and introduce a rigorous but fair interview.',
            'follow_up' => 'Challenge the candidate to defend decisions and evidence with respectful, probing follow-up questions.',
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

    public static function gender(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['gender'];
    }

    public static function voice(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['voice'];
    }

    public static function voiceStyle(mixed $key): string
    {
        return self::OPTIONS[self::normalize($key)]['voice_style'];
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
