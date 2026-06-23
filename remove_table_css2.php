<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $original = $content;
    
    $content = preg_replace('/^\s*\.custom-table[^{]*\{[^}]*\}/m', '', $content);
    $content = preg_replace('/^\s*\.nav-pills \.nav-link[^{]*\{[^}]*\}/m', '', $content);

    // clean up empty <style> tags
    $content = preg_replace('/<style>\s*<\/style>/m', '', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Modified " . basename($path) . "\n";
        $count++;
    }
}
echo "Total modified: $count\n";
