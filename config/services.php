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

 'ai_tts' => [
 'enabled' => env('AI_TTS_ENABLED', false),
 'provider' => env('AI_TTS_PROVIDER', env('AI_PROVIDER', 'openai')),
 'timeout' => env('AI_TTS_TIMEOUT', 30),
 ],

 'ai_transcription' => [
 'provider_priority' => env('AI_TRANSCRIPTION_PROVIDER_PRIORITY', 'openai,gemini'),
 'chunk_ms' => env('AI_TRANSCRIPTION_CHUNK_MS', 1200),
 'mobile_chunk_ms' => env('AI_TRANSCRIPTION_MOBILE_CHUNK_MS', 1500),
 'drain_timeout_ms' => env('AI_TRANSCRIPTION_DRAIN_TIMEOUT_MS', 20000),
 'request_timeout_ms' => env('AI_TRANSCRIPTION_REQUEST_TIMEOUT_MS', 30000),
 'max_in_flight' => env('AI_TRANSCRIPTION_MAX_IN_FLIGHT', 2),
 'rate_limit_cooldown_seconds' => env('AI_TRANSCRIPTION_RATE_LIMIT_COOLDOWN_SECONDS', 90),
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

 'gemini' => [
 'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-3.1-flash-tts-preview'),
 'tts_voice' => env('GEMINI_TTS_VOICE', 'Kore'),
 'tts_style' => env('GEMINI_TTS_STYLE', 'Say in a warm, clear, professional interviewer voice with natural English pronunciation and steady pacing'),
 'transcription_model' => env('GEMINI_TRANSCRIPTION_MODEL', env('GEMINI_MODEL', 'gemini-3.6-flash')),
 ],

 'elevenlabs' => [
 'api_key' => env('ELEVENLABS_API_KEY'),
 'api_endpoint' => env('ELEVENLABS_API_URL', 'https://api.elevenlabs.io/v1'),
 'tts_model' => env('ELEVENLABS_TTS_MODEL', 'eleven_multilingual_v2'),
 'tts_voice_id' => env('ELEVENLABS_TTS_VOICE_ID', 'XrExE9yKIg1WjnnlVkGX'),
 'tts_output_format' => env('ELEVENLABS_TTS_OUTPUT_FORMAT', 'mp3_44100_128'),
 'tts_language_code' => env('ELEVENLABS_TTS_LANGUAGE_CODE'),
 'tts_stability' => env('ELEVENLABS_TTS_STABILITY', 0.45),
 'tts_similarity_boost' => env('ELEVENLABS_TTS_SIMILARITY_BOOST', 0.75),
 'tts_style' => env('ELEVENLABS_TTS_STYLE', 0.15),
 'tts_speaker_boost' => env('ELEVENLABS_TTS_SPEAKER_BOOST', true),
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

 'local_feedback_model' => [
 'enabled' => env('LOCAL_FEEDBACK_MODEL_ENABLED', false),
 'python' => env('LOCAL_FEEDBACK_MODEL_PYTHON', env('LOCAL_SPEECH_PYTHON', 'python')),
 'train_script' => env('LOCAL_FEEDBACK_MODEL_TRAIN_SCRIPT', 'scripts/train_feedback_model.py'),
 'predict_script' => env('LOCAL_FEEDBACK_MODEL_PREDICT_SCRIPT', 'scripts/predict_feedback.py'),
 'model_path' => env('LOCAL_FEEDBACK_MODEL_PATH', 'storage/app/private/models/feedback/latest/model.json'),
 'timeout' => env('LOCAL_FEEDBACK_MODEL_TIMEOUT', 20),
 'training_timeout' => env('LOCAL_FEEDBACK_MODEL_TRAINING_TIMEOUT', 300),
 'auto_train_enabled' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN', false),
 'auto_train_time' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_TIME', '02:30'),
 'auto_train_dataset' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_DATASET', 'normalized/training/feedback_train.jsonl'),
 'auto_train_statuses' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_STATUSES', 'approved,archived'),
 'auto_train_min_examples' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_MIN_EXAMPLES', 100),
 'auto_train_epochs' => env('LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_EPOCHS', 80),
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
