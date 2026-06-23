<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $original = $content;
    
    // Pattern for the basic .custom-table th, td, tr
    $pattern1 = '/\s*\.custom-table th \{[\s\S]*?\}\s*\.custom-table td \{[\s\S]*?\}\s*\.custom-table tr:last-child td \{[^\}]*?\}/m';
    $content = preg_replace($pattern1, '', $content);
    
    // Pattern for nav-link
    $pattern2 = '/\s*\.nav-pills \.nav-link \{[\s\S]*?\}\s*\.nav-pills \.nav-link\.active \{[\s\S]*?\}/m';
    $content = preg_replace($pattern2, '', $content);

    // Pattern for the extended admin dashboard / notifications mobile table logic
    $pattern3 = '/\s*\.custom-table, \.custom-table tbody, \.custom-table tr, \.custom-table td \{[\s\S]*?\.custom-table td:last-child \{[\s\S]*?\}/m';
    $content = preg_replace($pattern3, '', $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Modified " . basename($path) . "\n";
        $count++;
    }
}

echo "Total modified: $count\n";
