<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'endpoint' => env('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email'),
        'timeout' => env('BREVO_API_TIMEOUT', 15),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'connect_timeout' => env('GOOGLE_HTTP_CONNECT_TIMEOUT', 3),
        'timeout' => env('GOOGLE_HTTP_TIMEOUT', 8),
    ],

    'openai' => [
        'tts_enabled' => env('AI_TTS_ENABLED', false),
        'tts_model' => env('OPENAI_TTS_MODEL', 'gpt-4o-mini-tts'),
        'tts_voice' => env('OPENAI_TTS_VOICE', 'alloy'),
        'tts_speed' => env('OPENAI_TTS_SPEED', 0.95),
        'tts_timeout' => env('AI_TTS_TIMEOUT', 30),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-transcribe'),
        'transcription_timeout' => env('AI_TRANSCRIPTION_TIMEOUT', 45),
    ],

    'local_speech' => [
        'enabled' => env('LOCAL_SPEECH_ENABLED', false),
        'python' => env('LOCAL_SPEECH_PYTHON', 'python'),
        'script' => env('LOCAL_SPEECH_SCRIPT', 'scripts/local_speech_assess.py'),
        'timeout' => env('LOCAL_SPEECH_TIMEOUT', 90),
        'asr_backend' => env('LOCAL_ASR_BACKEND', 'whisper'),
        'asr_model' => env('LOCAL_ASR_MODEL', 'base'),
        'asr_device' => env('LOCAL_ASR_DEVICE', 'auto'),
        'pronunciation_backend' => env('LOCAL_PRONUNCIATION_BACKEND', 'ctc'),
        'pronunciation_model' => env('LOCAL_PRONUNCIATION_MODEL', 'facebook/wav2vec2-base-960h'),
        'alignment_backend' => env('LOCAL_ALIGNMENT_BACKEND', 'mfa'),
        'mfa_command' => env('MFA_COMMAND', 'mfa'),
        'mfa_dictionary' => env('MFA_DICTIONARY'),
        'mfa_acoustic_model' => env('MFA_ACOUSTIC_MODEL'),
        'ffmpeg_command' => env('FFMPEG_COMMAND', 'ffmpeg'),
        'gop_backend' => env('LOCAL_GOP_BACKEND', 'mfa'),
        'gop_command' => env('LOCAL_GOP_COMMAND'),
    ],

];
