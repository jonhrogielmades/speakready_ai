<?php

namespace App\Console\Commands;

use App\Services\QuestionRecommendationService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeQuestionReranker extends Command
{
 protected $signature = 'ai:serve-question-reranker
 {--host= : Host to bind. Defaults to QUESTION_RERANK_SERVER_HOST.}
 {--port= : Port to bind. Defaults to QUESTION_RERANK_SERVER_PORT.}
 {--no-warm : Start without warming the model first.}';

 protected $description = 'Serve the trained question reranker as a warm local HTTP service.';

 public function handle(QuestionRecommendationService $recommendations): int
 {
 $script = $recommendations->rerankServerScriptPath();
 if (! is_file($script)) {
 $this->error("Question reranker server script is missing: {$script}");

 return self::FAILURE;
 }

 if (! $recommendations->trainedModelAvailable()) {
 $this->error('The trained question model files are not available.');

 return self::FAILURE;
 }

 $host = trim((string) ($this->option('host')?: config('services.question_recommender.rerank_server_host', '127.0.0.1')))?: '127.0.0.1';
 $port = (int) ($this->option('port')?: config('services.question_recommender.rerank_server_port', 8765));
 $port = max(1, min(65535, $port));

 $command = [
 $recommendations->pythonBinary(),
 $script,
 '--host',
 $host,
 '--port',
 (string) $port,
 '--model',
 $recommendations->trainedModelPath(),
 '--label-map',
 $recommendations->trainedModelLabelMapPath(),
 ];

 if (! $this->option('no-warm')) {
 $command[] = '--warm';
 }

 $this->info("Starting warm question reranker at http://{$host}:{$port}/rerank");
 $this->line('Keep this process running for fast model-backed question selection.');

 $process = new Process($command, base_path());
 $process->setTimeout(null);
 $process->run(function (string $type, string $buffer): void {
 $this->output->write($buffer);
 });

 return $process->isSuccessful()? self::SUCCESS: self::FAILURE;
 }
}
