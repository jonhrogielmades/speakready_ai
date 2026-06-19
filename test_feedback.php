<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$app->make('router')->get('/test/admin/feedback', [\App\Http\Controllers\AdminFeedbackController::class, 'index']);

$request = Illuminate\Http\Request::create('/test/admin/feedback', 'GET');
$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
$content = $response->getContent();
if (strpos($content, 'No feedback records found') !== false) {
    echo "Found 'No feedback records found'\n";
} else {
    echo "Records exist in HTML\n";
    preg_match_all('/<td>#(\d+)<\/td>/', $content, $matches);
    echo "Matched IDs: " . implode(', ', $matches[1]) . "\n";
}
echo "Total from stats: ";
preg_match('/<h3[^>]*>(\d+)<\/h3>/', $content, $matches2);
echo $matches2[1] ?? 'Not found';
echo "\n";
