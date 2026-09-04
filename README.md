# SpeakReady AI

SpeakReady AI is a Laravel-based interview preparation and career readiness platform. It combines mock interview sessions, AI-assisted feedback, learning modules, learning games, progress reporting, mentor review links, and an admin console for managing the whole system.

The current system includes separate desktop and mobile Blade experiences, a redesigned guest/landing experience, legal and security pages, richer user dashboards, AI provider fallbacks, local speech assessment hooks, Render deployment hardening, and automated schema repair commands for production reliability.

## Repository

- GitHub: https://github.com/jonhrogielmades/speakready_ai.git
- Main branch: `main`
- Framework: Laravel 12
- Runtime: PHP 8.2 or higher
- Front end build tool: Vite

## Current System Highlights

- Responsive guest landing pages with separate desktop and mobile CSS in `public/css/desktop/guest.css` and `public/css/mobile/guest.css`.
- Desktop preview images for the landing/product walkthrough in `public/img/desktop-preview`.
- Email/password authentication, password reset, Google OAuth login/register, logout, and account reactivation requests.
- User dashboard with interview history, progress summaries, recommendations, readiness metrics, notifications, and quick access to practice tools.
- Mock interview setup and live interview session flow with text answers, speech transcription, answer retries, state saving, abort, finish, review, and share controls.
- AI coaching, feedback generation, interview chat replies, and attachment/text extraction support.
- Learning modules with chapters, resources, quizzes, progress tracking, and personalized recommendations.
- Learning Games with admin-generated levels, user sessions, answer scoring, progress, energy, and downloadable certificates.
- Progress, feedback, reports, session exports, skills/perks, and account settings.
- Public shared review pages with optional unlock flow and mentor comments.
- Public contact form, newsletter subscription response, privacy policy, terms of service, security page, and cookie preferences page.
- Admin console for users, categories, questions, modules, learning games, interview sessions, contacts, feedback audits, AI providers, settings, notifications, and activity logs.
- Render-focused production startup script that binds early, runs migrations, repairs known schema drift, links storage, seeds the admin account, and rebuilds Laravel caches.

## Tech Stack

- Laravel 12 with Blade views
- PHP 8.2+ with PDO MySQL and PDO PostgreSQL support
- MySQL for local development by default
- PostgreSQL support for Render production through `DATABASE_URL`
- Laravel Sanctum, Socialite, Breeze scaffolding dependencies, and Tinker
- Jenssegers Agent for device-aware desktop/mobile view selection
- Vite 8 for front-end asset builds
- PHPUnit 11 for feature and reliability tests
- Optional Python speech pipeline through `scripts/local_speech_assess.py`

## Main Feature Areas

### Public And Guest Pages

- `/` renders the guest landing page when signed out.
- Signed-in admins are redirected to `/admin/dashboard`.
- Signed-in regular users are redirected to `/dashboard`.
- `/contact/send` stores contact messages and attempts to email the current admin.
- `/newsletter/subscribe` returns a subscription confirmation.
- `/privacy-policy`, `/terms-of-service`, `/security`, and `/cookie-preferences` render the new legal pages through `LegalPageController`.

### Authentication

- Register, login, logout, and password reset routes are handled by `AuthController`.
- Google login/register uses Socialite and the `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` environment variables.
- Deleted or inactive users can request reactivation, and admins can approve those requests.

### User Workspace

Regular users can access:

- `/dashboard` for the main readiness dashboard.
- `/interview/setup` and `/interview/session` for mock interview practice.
- `/progress`, `/feedback`, `/reports`, and `/session/{id}/review` for results and reflection.
- `/coach` for AI coaching conversations.
- `/learning`, `/modules`, `/modules/{id}`, and `/skills` for learning content and unlockable perks.
- `/notifications` and `/account` for utility workflows.

### Interviews And Feedback

Interview sessions support:

- Category, resume, and job-description context.
- Live answer submission and saved in-progress session state.
- Speech-to-text through OpenAI when configured, with browser speech APIs as no-key fallback behavior where the UI supports it.
- Optional local speech assessment for ASR evidence, pronunciation evidence, alignment data, and GOP integration.
- Evidence-based coaching and AI provider fallback handling when external providers are unavailable or return incomplete JSON.
- Admin-side KNN readiness matching support, comparing a scored interview with similar historical scored sessions as a secondary readiness signal.
- Shareable review links and mentor comments for external review.

### KNN Readiness Matching

The system includes a K-Nearest Neighbors readiness match through `App\Services\KnnReadinessClassifier`.
It uses score features such as clarity, relevance, grammar, professionalism, confidence, delivery stability,
job evidence match, and STAR method when those fields are available.

The weighted Euclidean distance formula is:

```text
d = sqrt(sum(weight_i * (target_i - neighbor_i)^2) / sum(weight_i))
```

The classifier selects the nearest scored sessions, applies inverse-distance weighting, predicts a readiness
score and band, and reports a confidence/reliability band. This is a comparison aid, not a guarantee of hiring
success or perfect scoring accuracy.

### Readiness Algorithm Suite

The admin dashboard also runs `App\Services\ReadinessAlgorithmSuite` as a secondary analysis layer. It does not replace
the saved readiness score; it adds explainable checks that support readiness review and module recommendations.

Implemented algorithms:

- Weighted Scoring: `(sum(score_i * weight_i)) / sum(weight_i)`
- TF-IDF Cosine Similarity: `(A dot B) / (||A|| * ||B||)`
- Decision Tree: `IF/ELSE` score thresholds for readiness bands
- Naive Bayes: `argmax P(class) * product P(feature_i | class)`
- Logistic Regression: `1 / (1 + e^-(b0 + b1x1 + ... + bnxn))`
- K-Means Clustering: assign score patterns to nearest centroids
- Random Forest: majority vote from multiple bootstrapped decision trees

### Learning Games

The system uses "Learning Games" terminology throughout the user and admin UI. Game features include:

- Admin-created or AI-generated game levels.
- Category-linked game practice.
- User game sessions and answer records.
- Energy and progression tracking.
- Learning guidance, success criteria, scoring, and certificates.
- Downloadable certificates by category.

### Admin Console

Admins can access:

- `/admin/dashboard` for overview metrics.
- `/admin/settings` for system settings.
- `/admin/users` for user management, exports, status updates, delete/restore-related flows, and reactivation approval.
- `/admin/categories` for interview and game categories.
- `/admin/questions` for question CRUD, bulk delete, import, export, analytics, dataset imports, and AI generation.
- `/admin/modules` for learning modules, chapters, resources, quizzes, quiz questions, AI generation, and game-level attachments.
- `/admin/game` for Learning Game level management and AI generation.
- `/admin/sessions` for session monitoring, archive, restore, flagging, deletion, review, and CSV export.
- `/admin/contacts` for stored contact messages.
- `/admin/feedback` for feedback audits, complaints, status updates, notes, and export.
- `/admin/ai/providers` for AI provider configuration, primary provider selection, and fallback provider selection.
- `/admin/notifications` and admin activity APIs for announcements and activity cleanup.

## Project Structure

```text
app/
  Console/Commands/        Custom schema repair, dataset, and maintenance commands
  Helpers/ViewHelper.php   Device-aware `mobile_view()` helper
  Http/Controllers/        Public, user, admin, auth, interview, and game controllers
  Models/                  Eloquent models for interviews, learning, games, AI, contacts, and users
  Services/                AI, speech, scoring, recommendations, exports, and readiness services
config/                    Laravel app, database, mail, services, session, cache, and filesystem config
database/
  migrations/              Full application schema
  seeders/                 Admin account and optional category/game seeders
public/
  css/desktop/             Desktop-specific UI styles
  css/mobile/              Mobile-specific UI styles
  img/desktop-preview/     Landing preview images
  js/                      Shared front-end scripts and vendor helpers
resources/views/
  desktop/                 Desktop Blade pages and layouts
  mobile/                  Mobile Blade pages and layouts
  legal/                   Shared legal page view
routes/web.php             Public, user, and admin web routes
scripts/local_speech_assess.py
tests/Feature/             Feature, smoke, hardening, and route tests
Dockerfile
render-start.sh            Render startup and maintenance entrypoint
```

## Requirements

Install these before running the app locally:

- PHP 8.2 or higher
- Composer
- Node.js 20.19 or higher, or Node.js 22.12 or higher
- npm 10 or higher
- MySQL, MariaDB, PostgreSQL, or SQLite
- Git
- Optional: Python 3, FFmpeg, Whisper/faster-whisper/transformers, and Montreal Forced Aligner for local speech assessment

## Local Setup

### 1. Clone The Repository

```bash
git clone https://github.com/jonhrogielmades/speakready_ai.git
cd speakready_ai
```

### 2. Install Dependencies

```bash
composer install
npm install
```

If Composer cannot find PHP on Windows, open a Laragon terminal or add Laragon's active PHP folder to your PATH.

### 3. Create The Environment File

```bash
cp .env.example .env
```

On Windows Command Prompt:

```cmd
copy .env.example .env
```

### 4. Configure Core Environment Values

Update `.env` for local development:

```env
APP_NAME="SpeakReady AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=speakready_ai
DB_USERNAME=root
DB_PASSWORD=
```

Create the empty `speakready_ai` database before running migrations.

### 5. Generate The App Key

```bash
php artisan key:generate
```

### 6. Run Migrations And Seed The Admin Account

```bash
php artisan migrate --seed
```

`DatabaseSeeder` seeds the admin account only. By default it creates:

```text
Email: admin@speakreadyai.com
Password: password
```

For a safer seeded admin account, set these before seeding:

```env
ADMIN_EMAIL=admin@example.com
ADMIN_NAME="System Admin"
ADMIN_PASSWORD=change-this-password
```

There is no tracked `database_dump.sql` in this repository, so migrations are the source of truth for setup.

Optional starter content seeders are available if you want baseline categories or Learning Game levels:

```bash
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=GameLevelSeeder
```

### 7. Link Public Storage

```bash
php artisan storage:link
```

### 8. Start The Front-End Build

```bash
npm run dev
```

Keep this terminal running while developing.

### 9. Start Laravel

Open a second terminal:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Then open http://localhost:8000.

## Production Build

For a production asset build:

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not commit generated `public/build`, `vendor`, `node_modules`, `.env`, SQL dumps, ZIP files, logs, or temporary audit folders.

## Environment Reference

### App And Session

```env
APP_NAME="SpeakReady AI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
```

When deployed on Render, `config/app.php` and `render-start.sh` can use `RENDER_EXTERNAL_URL` if `APP_URL` is missing or still points to localhost.

### Database

Local MySQL example:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=speakready_ai
DB_USERNAME=root
DB_PASSWORD=
```

Render PostgreSQL example:

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://user:password@host:5432/database
DB_SSLMODE=require
RENDER_POSTGRES_REGION=singapore
```

Use the full internal database URL when the Render web service and database are in the same account and region. Use the full external database URL otherwise. Do not use only a partial host such as `dpg-...-a`; the app includes fallback expansion for partial Render hosts, but the full URL is still the preferred setup.

### Mail And Password Reset

Local mailpit-style development from `.env.example`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail SMTP example:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=yourgmail@gmail.com
MAIL_PASSWORD="your-google-app-password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=yourgmail@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail requires a Google App Password. Render Free web services block common SMTP ports, so use a paid Render instance for SMTP or use an HTTPS/API mail provider.

Brevo API example for Render Free password reset emails:

```env
BREVO_API_KEY=your_brevo_api_key
MAIL_FROM_ADDRESS=your_verified_sender@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Google OAuth

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

For production, change `GOOGLE_REDIRECT_URI` to your HTTPS domain callback URL.

### AI Providers

The app can use multiple AI providers and fall back by priority:

```env
AI_PROVIDER=huggingface
INTERVIEW_CHATBOT_DEFAULT_PROVIDER=huggingface
AI_DEFAULT_PROVIDER_PRIORITY=huggingface,gemini,groq,openrouter,cohere
INTERVIEW_CHATBOT_PROVIDER_PRIORITY=huggingface,gemini,groq,openrouter,cohere
AI_FEEDBACK_PROVIDER_PRIORITY=huggingface,gemini,groq,openrouter,cohere
AI_ATTACHMENT_EXTRACTION_PROVIDER_PRIORITY=gemini

HUGGINGFACE_API_KEY=
GEMINI_API_KEY=
GROQ_API_KEY=
OPENROUTER_API_KEY=
COHERE_API_KEY=
OPENAI_API_KEY=
```

Provider URLs and model names are already listed in `.env.example`. Configure at least one provider key for AI generation, coaching, and feedback features.

Recommended hosted AI runtime limits:

```env
AI_FEEDBACK_TIMEOUT=15
AI_FEEDBACK_DEADLINE_SECONDS=25
AI_FEEDBACK_MAX_PROVIDERS=6
AI_FEEDBACK_ATTEMPTS=1
AI_FEEDBACK_HTTP_ATTEMPTS=1
AI_FEEDBACK_RETRY_DELAY_MS=200
AI_VOICE_ANALYSIS_TIMEOUT=12
AI_VOICE_ANALYSIS_MAX_PROVIDERS=2
AI_VOICE_ANALYSIS_HTTP_ATTEMPTS=1
AI_PROVIDER_TIMEOUT=45
AI_PROVIDER_CONNECT_TIMEOUT=5
AI_PROVIDER_RETRIES=2
AI_PROVIDER_RETRY_DELAY_MS=250
AI_JSON_MAX_TOKENS=4096
AI_CHAT_MAX_TOKENS=1000
```

The `AI_VOICE_ANALYSIS_*` values are reserved for hosted voice-analysis tuning. Current voice recording, transcription, and delivery coaching behavior is controlled by the transcription, TTS, and feedback settings below.

### Local Feedback Model Training

SpeakReady can train a private local feedback scoring model from reviewed interview answers. This is meant for answer scoring and coaching support, not question generation or general chat.

Export approved/archived admin-reviewed feedback into JSONL:

```bash
php artisan ai:export-feedback-training
```

Train the model inside the app:

```bash
php artisan ai:train-feedback-model
```

The default model artifact is stored privately at:

```text
storage/app/private/models/feedback/latest/model.json
```

After training, enable it for final interview feedback:

```env
LOCAL_FEEDBACK_MODEL_ENABLED=true
AI_FEEDBACK_PROVIDER_PRIORITY=localmodel,huggingface,gemini,groq,openrouter,cohere
```

Automatic retraining can run through Laravel's scheduler. It exports reviewed labels first, compares the dataset checksum with the current model, and trains only when the labels changed:

```env
LOCAL_FEEDBACK_MODEL_AUTO_TRAIN=true
LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_TIME=02:30
LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_MIN_EXAMPLES=100
LOCAL_FEEDBACK_MODEL_AUTO_TRAIN_EPOCHS=80
```

Run the automatic check manually:

```bash
php artisan ai:auto-train-feedback-model
```

For production auto training, make sure Laravel's scheduler runs every minute through cron or a worker process:

```bash
php artisan schedule:run
```

Use at least 100 reviewed answers before trusting the model for real scoring. With fewer examples, pass `--force` to train a smoke-test model only:

```bash
php artisan ai:train-feedback-model --force
```

### Speech, Transcription, And Voice Analysis

Browser live captions are used first for real-time interview transcription where supported. OpenAI/Gemini transcription can be configured as a cloud fallback when browser speech recognition is unavailable or fails. Cloud fallback availability still depends on provider API quotas.

```env
OPENAI_API_KEY=
AI_TRANSCRIPTION_PROVIDER_PRIORITY=openai,gemini
OPENAI_TRANSCRIPTION_MODEL=gpt-transcribe
AI_TRANSCRIPTION_TIMEOUT=45
AI_TRANSCRIPTION_CHUNK_MS=4000
AI_TRANSCRIPTION_MOBILE_CHUNK_MS=5000
AI_TRANSCRIPTION_DRAIN_TIMEOUT_MS=20000
AI_TRANSCRIPTION_REQUEST_TIMEOUT_MS=30000
AI_TRANSCRIPTION_MAX_IN_FLIGHT=2
AI_TRANSCRIPTION_RATE_LIMIT_COOLDOWN_SECONDS=90
AI_VOICE_ANALYSIS_TIMEOUT=12
AI_VOICE_ANALYSIS_MAX_PROVIDERS=2
AI_VOICE_ANALYSIS_HTTP_ATTEMPTS=1
AI_TTS_ENABLED=false
OPENAI_TTS_MODEL=gpt-4o-mini-tts
OPENAI_TTS_VOICE=alloy
OPENAI_TTS_SPEED=0.95
```

Optional local speech assessment:

```env
LOCAL_SPEECH_ENABLED=false
LOCAL_SPEECH_PYTHON=python
LOCAL_SPEECH_SCRIPT=scripts/local_speech_assess.py
LOCAL_SPEECH_TIMEOUT=90
LOCAL_ASR_BACKEND=whisper
LOCAL_ASR_MODEL=base
LOCAL_ASR_DEVICE=auto
LOCAL_PRONUNCIATION_BACKEND=ctc
LOCAL_PRONUNCIATION_MODEL=facebook/wav2vec2-base-960h
LOCAL_ALIGNMENT_BACKEND=mfa
MFA_COMMAND=mfa
MFA_DICTIONARY=
MFA_ACOUSTIC_MODEL=
FFMPEG_COMMAND=ffmpeg
LOCAL_GOP_BACKEND=mfa
LOCAL_GOP_COMMAND=
```

Install the selected Python tools first. If `LOCAL_GOP_COMMAND` is not configured, the app does not invent a true GOP score; it reports GOP as unavailable or uses limited proxy evidence where supported.

## Render Deployment

This repository includes `Dockerfile`, `railpack.json`, `nginx.conf`, and `render-start.sh`.

Recommended Render settings:

- Environment: Docker or Railpack-compatible PHP runtime
- PHP package version: 8.3 when using `railpack.json`
- Start command: handled by the Docker `CMD` when using the included Dockerfile
- Public port: `$PORT`, defaulting to `10000`
- Database: Render PostgreSQL with `DATABASE_URL`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true`
- `CACHE_DRIVER=file`
- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=file`

On startup, `render-start.sh`:

- Sets `APP_URL` from `RENDER_EXTERNAL_URL` when needed.
- Warns when Gmail SMTP is configured on Render Free.
- Expands partial Render PostgreSQL hosts if necessary.
- Creates required storage and cache directories.
- Starts PHP-FPM and binds Nginx early.
- Clears stale Laravel caches.
- Runs schema repair commands for AI providers, voice sessions, questions, interview answers, scores, feedback, and game tables.
- Runs migrations and seeds the admin account.
- Links storage and rebuilds optimized caches.

Optional production maintenance:

```env
RENDER_REPAIR_FEEDBACK_ON_START=true
RENDER_REPAIR_FEEDBACK_LIMIT=250
```

Use this only when older completed interviews need feedback coaching backfill during startup.

Do not set `RUN_RENDER_DATA_CLEANUP=true` during normal deploys. That maintenance flag intentionally wipes user data while preserving the admin account.

## Useful Artisan Commands

```bash
php artisan datasets:check
php artisan app:ensure-ai-provider-schema --force --create-missing
php artisan app:ensure-voice-schema --force --create-missing
php artisan app:ensure-question-schema --force --create-missing
php artisan app:ensure-interview-answer-schema --force --create-missing
php artisan app:ensure-score-schema --force --create-missing
php artisan app:ensure-feedback-schema --force --create-missing
php artisan app:ensure-game-schema --force
php artisan app:normalize-id-sequences --force
php artisan app:repair-feedback-coaching --limit=250
php artisan ai:export-feedback-training
php artisan ai:train-feedback-model
php artisan ai:auto-train-feedback-model
```

## Testing

Run the feature test suite:

```bash
php artisan test
```

Run a focused test file:

```bash
php artisan test tests/Feature/RouteIntegrityTest.php
```

The current feature tests cover authentication, password reset, route integrity, page smoke checks, mobile layout behavior, landing stats, admin flows, AI provider schema repair, question/voice schema repair, interview security, learning game guidance, progress/report accuracy, user utilities, and hardening paths.

## Troubleshooting

- `500 Server Error`: check `.env`, generate `APP_KEY`, verify database credentials, run migrations, then run `php artisan optimize:clear`.
- CSS or JavaScript missing: run `npm install`, then `npm run dev` for local work or `npm run build` for production.
- Uploaded files or audio are not loading: run `php artisan storage:link`.
- Login or session problems in production: verify `APP_URL`, HTTPS, `SESSION_SECURE_COOKIE`, and cache state.
- Google login fails: verify Google OAuth credentials and callback URL.
- Password reset emails do not send: check mail credentials; on Render Free use Brevo/API mail instead of SMTP.
- AI features return fallback messages: configure at least one provider API key and check provider priority values.
- Speech analysis is incomplete: configure OpenAI transcription or enable and install the local speech pipeline tools.
- Render database host cannot resolve: use the full `DATABASE_URL` or set `RENDER_POSTGRES_REGION` for partial Render hosts.
- Schema drift after deployment: run the relevant `app:ensure-*` command or redeploy so `render-start.sh` runs maintenance.

## Notes For Maintainers

- `mobile_view()` selects `desktop.*` or `mobile.*` Blade templates based on request device attributes.
- Guest layout CSS was moved into dedicated desktop/mobile CSS files to keep guest Blade layouts smaller and easier to maintain.
- Legal pages share `resources/views/legal/show.blade.php` and are populated by `LegalPageController`.
- Learning Games are the current terminology and replaced older Arena wording in the UI and data model.
- Contact messages are saved to the database before email is attempted, so support requests are not lost if SMTP fails.
- Temporary Codex audit and browser profile folders are ignored by Git and should not be committed.
