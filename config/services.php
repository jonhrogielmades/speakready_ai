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
 'connect_timeout' => env('GOOGLE_HTTP_CONNECT_TIMEOUT', 5),
 'timeout' => env('GOOGLE_HTTP_TIMEOUT', 20),
 ],

 'webpush' => [
 'subject' => env('WEBPUSH_VAPID_SUBJECT', env('APP_URL')),
 'public_key' => env('WEBPUSH_VAPID_PUBLIC_KEY'),
 'private_key' => env('WEBPUSH_VAPID_PRIVATE_KEY'),
 'auto_generate' => env('WEBPUSH_AUTO_GENERATE', true),
 'storage_path' => env('WEBPUSH_VAPID_STORAGE_PATH'),
 ],

 'ai_tts' => [
 'enabled' => env('AI_TTS_ENABLED', false),
 'provider' => env('AI_TTS_PROVIDER', 'elevenlabs,openai,gemini'),
 'timeout' => env('AI_TTS_TIMEOUT', 30),
 ],

 'ai_transcription' => [
 'provider_priority' => env('AI_TRANSCRIPTION_PROVIDER_PRIORITY', 'openai,gemini'),
 'http_attempts' => env('AI_TRANSCRIPTION_HTTP_ATTEMPTS', 3),
 'retry_delay_ms' => env('AI_TRANSCRIPTION_RETRY_DELAY_MS', 500),
 'connect_timeout_seconds' => env('AI_TRANSCRIPTION_CONNECT_TIMEOUT', 10),
 'total_timeout_seconds' => env('AI_TRANSCRIPTION_TOTAL_TIMEOUT', 105),
 'finish_recovery_timeout_seconds' => env('AI_TRANSCRIPTION_FINISH_RECOVERY_TIMEOUT', 20),
 'local_fallback_reserve_seconds' => env('AI_TRANSCRIPTION_LOCAL_FALLBACK_RESERVE_SECONDS', 20),
 'chunking_strategy' => env('AI_TRANSCRIPTION_CHUNKING_STRATEGY', 'auto'),
 'gemini_max_output_tokens' => env('AI_TRANSCRIPTION_GEMINI_MAX_OUTPUT_TOKENS', 8192),
 'recording_audio_bitrate' => env('AI_TRANSCRIPTION_RECORDING_BITRATE', 128000),
 'chunk_ms' => env('AI_TRANSCRIPTION_CHUNK_MS', 8000),
 'mobile_chunk_ms' => env('AI_TRANSCRIPTION_MOBILE_CHUNK_MS', 10000),
 'drain_timeout_ms' => env('AI_TRANSCRIPTION_DRAIN_TIMEOUT_MS', 20000),
 'request_timeout_ms' => env('AI_TRANSCRIPTION_REQUEST_TIMEOUT_MS', 30000),
 'full_request_timeout_ms' => env('AI_TRANSCRIPTION_FULL_REQUEST_TIMEOUT_MS', 120000),
 'max_in_flight' => env('AI_TRANSCRIPTION_MAX_IN_FLIGHT', 2),
 'rate_limit_cooldown_seconds' => env('AI_TRANSCRIPTION_RATE_LIMIT_COOLDOWN_SECONDS', 90),
 'rate_limit_per_minute' => env('AI_TRANSCRIBE_RATE_LIMIT_PER_MINUTE', 20),
 'proof_ttl_seconds' => env('AI_TRANSCRIPTION_PROOF_TTL_SECONDS', 1800),
 'ffprobe_command' => env('FFPROBE_COMMAND', 'ffprobe'),
 ],

 'openai' => [
 'api_key' => env('OPENAI_API_KEY'),
 'api_endpoint' => env('OPENAI_API_URL', 'https://api.openai.com/v1'),
 'model' => env('OPENAI_MODEL', 'gpt-6-astra'),
 'feedback_model' => env('OPENAI_FEEDBACK_MODEL', env('OPENAI_MODEL', 'gpt-6-astra')),
 'json_mode_fallback' => env('OPENAI_JSON_MODE_FALLBACK', false),
 'tts_enabled' => env('AI_TTS_ENABLED', false),
 'tts_model' => env('OPENAI_TTS_MODEL', 'gpt-4o-mini-tts'),
 'tts_voice' => env('OPENAI_TTS_VOICE', 'nova'),
 'tts_speed' => env('OPENAI_TTS_SPEED', 0.90),
 'tts_timeout' => env('AI_TTS_TIMEOUT', 30),
 'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-transcribe'),
 'transcription_timeout' => env('AI_TRANSCRIPTION_TIMEOUT', 90),
 'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'high'),
 ],

 'gemini' => [
 'api_key' => env('GEMINI_API_KEY'),
 'api_endpoint' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
 'model' => env('GEMINI_MODEL', 'gemini-4-argon'),
 'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-3.1-flash-tts-preview'),
 'tts_voice' => env('GEMINI_TTS_VOICE', 'Leda'),
 'tts_style' => env('GEMINI_TTS_STYLE', 'Say in a warm, clear, professional female interviewer voice with natural English pronunciation and medium, measured pacing'),
 'transcription_model' => env('GEMINI_TRANSCRIPTION_MODEL', env('GEMINI_MODEL', 'gemini-4-argon')),
 'thinking_level' => env('GEMINI_THINKING_LEVEL', 'high'),
 ],

 'groq' => [
 'api_key' => env('GROQ_API_KEY'),
 'api_endpoint' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
 'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
 ],

 'cohere' => [
 'api_key' => env('COHERE_API_KEY'),
 'api_endpoint' => env('COHERE_API_URL', 'https://api.cohere.com/v2/chat'),
 'model' => env('COHERE_MODEL', 'command-a-plus-05-2026'),
 ],

 'elevenlabs' => [
 'api_key' => env('ELEVENLABS_API_KEY'),
 'api_endpoint' => env('ELEVENLABS_API_URL', 'https://api.elevenlabs.io/v1'),
 'tts_model' => env('ELEVENLABS_TTS_MODEL', 'eleven_multilingual_v2'),
 'tts_voice_id' => env('ELEVENLABS_TTS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),
 'tts_output_format' => env('ELEVENLABS_TTS_OUTPUT_FORMAT', 'mp3_44100_128'),
 'tts_language_code' => env('ELEVENLABS_TTS_LANGUAGE_CODE'),
 'tts_stability' => env('ELEVENLABS_TTS_STABILITY', 0.45),
 'tts_similarity_boost' => env('ELEVENLABS_TTS_SIMILARITY_BOOST', 0.75),
 'tts_style' => env('ELEVENLABS_TTS_STYLE', 0.15),
 'tts_speed' => env('ELEVENLABS_TTS_SPEED', 0.90),
 'tts_speaker_boost' => env('ELEVENLABS_TTS_SPEAKER_BOOST', true),
 ],

 'algorithms' => [
 'min_quality_percent' => env('ALGORITHM_MIN_QUALITY_PERCENT', 95),
 'min_accuracy_percent' => env('ALGORITHM_MIN_ACCURACY_PERCENT', 95),
 'min_reliability_percent' => env('ALGORITHM_MIN_RELIABILITY_PERCENT', 95),
 'min_confidence_percent' => env('ALGORITHM_MIN_CONFIDENCE_PERCENT', 95),
 'min_evidence_count' => env('ALGORITHM_MIN_EVIDENCE_COUNT', 100),
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
 'require_true_gop_for_score' => env('LOCAL_PRONUNCIATION_REQUIRE_TRUE_GOP', true),
 'min_transcript_confidence' => env('LOCAL_SPEECH_MIN_TRANSCRIPT_CONFIDENCE', 95),
 'min_scoring_reliability' => env('LOCAL_SPEECH_MIN_SCORING_RELIABILITY', 95),
 ],

 'local_feedback_model' => [
 'enabled' => env('LOCAL_FEEDBACK_MODEL_ENABLED', true),
 'python' => env('LOCAL_FEEDBACK_MODEL_PYTHON', env('LOCAL_SPEECH_PYTHON', 'python')),
 'train_script' => env('LOCAL_FEEDBACK_MODEL_TRAIN_SCRIPT', 'scripts/train_feedback_model.py'),
 'predict_script' => env('LOCAL_FEEDBACK_MODEL_PREDICT_SCRIPT', 'scripts/predict_feedback.py'),
 'model_path' => env('LOCAL_FEEDBACK_MODEL_PATH', 'storage/app/private/models/feedback/latest/model.json'),
 'timeout' => env('LOCAL_FEEDBACK_MODEL_TIMEOUT', 20),
 'training_timeout' => env('LOCAL_FEEDBACK_MODEL_TRAINING_TIMEOUT', 300),
 'require_validation' => env('LOCAL_FEEDBACK_MODEL_REQUIRE_VALIDATION', true),
 'validation_fraction' => env('LOCAL_FEEDBACK_MODEL_VALIDATION_FRACTION', 0.20),
 'min_validation_examples' => env('LOCAL_FEEDBACK_MODEL_MIN_VALIDATION_EXAMPLES', 100),
 'max_validation_mae' => env('LOCAL_FEEDBACK_MODEL_MAX_VALIDATION_MAE', 5),
 'min_validation_within_10_rate' => env('LOCAL_FEEDBACK_MODEL_MIN_WITHIN_10_RATE', 0.95),
 'min_validation_skill' => env('LOCAL_FEEDBACK_MODEL_MIN_VALIDATION_SKILL', 0.10),
 'min_human_reviewed_ratio' => env('LOCAL_FEEDBACK_MODEL_MIN_HUMAN_REVIEWED_RATIO', 1.0),
 'auto_train_enabled' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN', false),
 'auto_train_time' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_TIME', '02:30'),
 'auto_train_dataset' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_DATASET', 'normalized/training/feedback_train.jsonl'),
 'auto_train_statuses' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_STATUSES', 'approved,archived'),
 'auto_train_min_examples' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_MIN_EXAMPLES', 500),
 'auto_train_epochs' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_EPOCHS', 80),
 ],

 'ai_feedback_review' => [
 'min_reliability_percent' => env('AI_FEEDBACK_REVIEW_MIN_RELIABILITY_PERCENT', 95),
 'retry_cooldown_minutes' => env('AI_FEEDBACK_REVIEW_RETRY_COOLDOWN_MINUTES', 10),
 'timeout_seconds' => env('AI_FEEDBACK_REVIEW_REFRESH_TIMEOUT', env('AI_FEEDBACK_TIMEOUT', 30)),
 'deadline_seconds' => env('AI_FEEDBACK_REVIEW_REFRESH_DEADLINE_SECONDS', env('AI_FEEDBACK_DEADLINE_SECONDS', 60)),
 'max_attempts' => env('AI_FEEDBACK_REVIEW_REFRESH_ATTEMPTS', env('AI_FEEDBACK_ATTEMPTS', 2)),
 'http_attempts' => env('AI_FEEDBACK_REVIEW_REFRESH_HTTP_ATTEMPTS', env('AI_FEEDBACK_HTTP_ATTEMPTS', 2)),
 'retry_delay_ms' => env('AI_FEEDBACK_REVIEW_REFRESH_RETRY_DELAY_MS', env('AI_FEEDBACK_RETRY_DELAY_MS', 250)),
 ],

 'ai_feedback' => [
 'provider_priority' => env('AI_FEEDBACK_PROVIDER_PRIORITY', env('INTERVIEW_CHATBOT_PROVIDER_PRIORITY', 'openai,gemini,groq,cohere')),
 'max_providers' => env('AI_FEEDBACK_MAX_PROVIDERS', 4),
 'timeout_seconds' => env('AI_FEEDBACK_TIMEOUT', 45),
 'deadline_seconds' => env('AI_FEEDBACK_DEADLINE_SECONDS', 150),
 'max_attempts' => env('AI_FEEDBACK_ATTEMPTS', 2),
 'http_attempts' => env('AI_FEEDBACK_HTTP_ATTEMPTS', 2),
 'retry_delay_ms' => env('AI_FEEDBACK_RETRY_DELAY_MS', 250),
 'max_output_tokens' => env('AI_FEEDBACK_MAX_OUTPUT_TOKENS'),
 ],

 'ai_feedback_finalization' => [
 'timeout_seconds' => env('AI_FEEDBACK_FINALIZATION_TIMEOUT', env('AI_FEEDBACK_TIMEOUT', 30)),
 'deadline_seconds' => env('AI_FEEDBACK_FINALIZATION_DEADLINE_SECONDS', env('AI_FEEDBACK_DEADLINE_SECONDS', 90)),
 'max_attempts' => env('AI_FEEDBACK_FINALIZATION_ATTEMPTS', 2),
 'http_attempts' => env('AI_FEEDBACK_FINALIZATION_HTTP_ATTEMPTS', env('AI_FEEDBACK_HTTP_ATTEMPTS', 2)),
 'retry_delay_ms' => env('AI_FEEDBACK_FINALIZATION_RETRY_DELAY_MS', env('AI_FEEDBACK_RETRY_DELAY_MS', 250)),
 ],

 'ai_game_feedback' => [
 'timeout_seconds' => env('AI_GAME_FEEDBACK_TIMEOUT', env('AI_FEEDBACK_TIMEOUT', 30)),
 'deadline_seconds' => env('AI_GAME_FEEDBACK_DEADLINE_SECONDS', env('AI_FEEDBACK_DEADLINE_SECONDS', 90)),
 'max_attempts' => env('AI_GAME_FEEDBACK_ATTEMPTS', env('AI_FEEDBACK_ATTEMPTS', 2)),
 'http_attempts' => env('AI_GAME_FEEDBACK_HTTP_ATTEMPTS', env('AI_FEEDBACK_HTTP_ATTEMPTS', 2)),
 'retry_delay_ms' => env('AI_GAME_FEEDBACK_RETRY_DELAY_MS', env('AI_FEEDBACK_RETRY_DELAY_MS', 250)),
 ],

 'ai_coach' => [
 'min_reliability_percent' => env('AI_COACH_MIN_RELIABILITY_PERCENT', 95),
 'timeout_seconds' => env('AI_COACH_CHAT_TIMEOUT', 20),
 'attempts' => env('AI_COACH_CHAT_ATTEMPTS', 1),
 'max_providers' => env('AI_COACH_CHAT_MAX_PROVIDERS', 3),
 ],

 'ai_chat' => [
 'temperature' => env('AI_CHAT_TEMPERATURE', 0.2),
 'max_tokens' => env('AI_CHAT_MAX_TOKENS', 1000),
 ],

 'ai_providers' => [
 'default_provider' => env('AI_PROVIDER', 'openai'),
 'default_provider_priority' => env('AI_DEFAULT_PROVIDER_PRIORITY', 'openai,gemini,groq,cohere'),
 'interview_default_provider' => env('INTERVIEW_CHATBOT_DEFAULT_PROVIDER', env('AI_PROVIDER', 'openai')),
 'interview_provider_priority' => env('INTERVIEW_CHATBOT_PROVIDER_PRIORITY', 'openai,gemini,groq,cohere'),
 'timeout_seconds' => env('AI_PROVIDER_TIMEOUT', 45),
 'connect_timeout_seconds' => env('AI_PROVIDER_CONNECT_TIMEOUT', 5),
 'attempts' => env('AI_PROVIDER_RETRIES', 2),
 'retry_delay_ms' => env('AI_PROVIDER_RETRY_DELAY_MS', 250),
 'smoke_timeout_seconds' => env('AI_PROVIDER_SMOKE_TIMEOUT', 20),
 'smoke_deadline_seconds' => env('AI_PROVIDER_SMOKE_DEADLINE_SECONDS', 45),
 'min_quality_percent' => env('AI_PROVIDER_MIN_QUALITY_PERCENT', 95),
 'min_accuracy_percent' => env('AI_PROVIDER_MIN_ACCURACY_PERCENT', 95),
 'min_reliability_percent' => env('AI_PROVIDER_MIN_RELIABILITY_PERCENT', 95),
 'min_schema_percent' => env('AI_PROVIDER_MIN_SCHEMA_PERCENT', 100),
 'min_safety_percent' => env('AI_PROVIDER_MIN_SAFETY_PERCENT', 100),
 'min_success_rate_percent' => env('AI_PROVIDER_MIN_SUCCESS_RATE_PERCENT', 95),
 'min_benchmark_samples' => env('AI_PROVIDER_MIN_BENCHMARK_SAMPLES', 3),
 'circuit_breaker_enabled' => env('AI_PROVIDER_CIRCUIT_BREAKER_ENABLED', true),
 'circuit_breaker_in_tests' => env('AI_PROVIDER_CIRCUIT_BREAKER_IN_TESTS', false),
 'circuit_failure_threshold' => env('AI_PROVIDER_CIRCUIT_FAILURE_THRESHOLD', 3),
 'circuit_cooldown_seconds' => env('AI_PROVIDER_CIRCUIT_COOLDOWN_SECONDS', 120),
 'allow_custom_endpoints' => env('AI_PROVIDER_ALLOW_CUSTOM_ENDPOINTS', false),
 'max_response_bytes' => env('AI_PROVIDER_MAX_RESPONSE_BYTES', 1048576),
 'max_retry_delay_ms' => env('AI_PROVIDER_MAX_RETRY_DELAY_MS', 5000),
 'json_mode_fallback' => env('AI_PROVIDER_JSON_MODE_FALLBACK', false),
 ],

 'question_recommender' => [
 'enabled' => env('QUESTION_RECOMMENDER_ENABLED', true),
 'python' => env('QUESTION_RECOMMENDER_PYTHON', env('LOCAL_SPEECH_PYTHON', 'python')),
 'script' => env('QUESTION_RECOMMENDER_SCRIPT', 'scripts/recommend_interview_question.py'),
 'rerank_script' => env('QUESTION_RERANK_SCRIPT', 'scripts/rerank_question_candidates.py'),
 'rerank_server_script' => env('QUESTION_RERANK_SERVER_SCRIPT', 'scripts/serve_question_reranker.py'),
 'rerank_server_enabled' => env('QUESTION_RERANK_SERVER_ENABLED', true),
 'rerank_endpoint' => env('QUESTION_RERANK_ENDPOINT', 'http://127.0.0.1:8765/rerank'),
 'rerank_server_host' => env('QUESTION_RERANK_SERVER_HOST', '127.0.0.1'),
 'rerank_server_port' => env('QUESTION_RERANK_SERVER_PORT', 8765),
 'rerank_server_timeout' => env('QUESTION_RERANK_SERVER_TIMEOUT', 1),
 'rerank_server_failure_cooldown' => env('QUESTION_RERANK_SERVER_FAILURE_COOLDOWN', 300),
 'index_path' => env('QUESTION_EMBEDDING_INDEX_PATH', 'storage/app/private/datasets/embeddings/questions/latest/question_embeddings.json'),
 'timeout' => env('QUESTION_RECOMMENDER_TIMEOUT', 5),
 'trained_model_enabled' => env('QUESTION_TRAINED_MODEL_ENABLED', true),
 'trained_model_path' => env('QUESTION_TRAINED_MODEL_PATH', 'storage/app/private/models/questions/latest/trained_model'),
 'trained_model_label_map_path' => env('QUESTION_TRAINED_MODEL_LABEL_MAP_PATH', 'storage/app/private/models/questions/latest/trained_model/speakready_question_labels.json'),
 'trained_model_validation_path' => env('QUESTION_TRAINED_MODEL_VALIDATION_PATH', 'storage/app/private/models/questions/latest/trained_model/validation_metrics.json'),
 'trained_model_require_validation' => env('QUESTION_TRAINED_MODEL_REQUIRE_VALIDATION', true),
 'trained_model_min_evaluation_examples' => env('QUESTION_TRAINED_MODEL_MIN_EVALUATION_EXAMPLES', 200),
 'trained_model_min_precision_at_5' => env('QUESTION_TRAINED_MODEL_MIN_PRECISION_AT_5', 0.95),
 'trained_model_timeout' => env('QUESTION_TRAINED_MODEL_TIMEOUT', 5),
 'trained_model_process_fallback_enabled' => env('QUESTION_TRAINED_MODEL_PROCESS_FALLBACK', false),
 'ai_provider_rerank_enabled' => env('QUESTION_AI_PROVIDER_RERANK_ENABLED', false),
 'ai_provider_timeout' => env('QUESTION_AI_PROVIDER_RERANK_TIMEOUT', 3),
 'ai_provider_attempts' => env('QUESTION_AI_PROVIDER_RERANK_ATTEMPTS', 1),
 'ai_provider_candidate_limit' => env('QUESTION_AI_PROVIDER_RERANK_CANDIDATE_LIMIT', 18),
 'ai_provider_failure_cooldown' => env('QUESTION_AI_PROVIDER_RERANK_FAILURE_COOLDOWN', 180),
 'ai_provider_priority' => env('QUESTION_AI_PROVIDER_RERANK_PRIORITY', env('AI_DEFAULT_PROVIDER_PRIORITY', 'openai,gemini,groq,cohere')),
 'skip_ai_provider_rerank_when_server_unavailable' => env('QUESTION_SKIP_AI_RERANK_WHEN_SERVER_UNAVAILABLE', true),
 'candidate_pool_limit' => env('QUESTION_RECOMMENDER_CANDIDATE_POOL_LIMIT', 40),
 'candidate_pool_multiplier' => env('QUESTION_RECOMMENDER_CANDIDATE_POOL_MULTIPLIER', 5),
 ],

 'interview_follow_up' => [
 'timeout' => env('AI_INTERVIEW_FOLLOW_UP_TIMEOUT', 6),
 'http_attempts' => env('AI_INTERVIEW_FOLLOW_UP_HTTP_ATTEMPTS', 1),
 'max_retries' => env('AI_INTERVIEW_FOLLOW_UP_RETRIES', 1),
 'max_providers' => env('AI_INTERVIEW_FOLLOW_UP_MAX_PROVIDERS', 1),
 ],

 'question_generation_rag' => [
 'enabled' => env('QUESTION_GENERATION_RAG_ENABLED', true),
 'example_limit' => env('QUESTION_GENERATION_RAG_EXAMPLE_LIMIT', 6),
 ],

];
