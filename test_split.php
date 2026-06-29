<?php
$text = "Describe a recent situation where you felt shy. 2. What thoughts cross your mind in those moments? 3. How does shyness affect your interactions? 4. Identify one small step you can take to overcome it. 5. What resources would support your growth? 6. How would you measure progress? 7. Who can you share your goal with for accountability?";

$normalizedMissionText = preg_replace('/\s+(\d+[\.\)])\s+/', "\n$1 ", $text);
$lines = array_filter(array_map('trim', explode("\n", $normalizedMissionText)));

print_r($lines);
