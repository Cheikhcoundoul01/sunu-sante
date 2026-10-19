<?php
$file = 'C:\Users\USER\Desktop\SunuSanté\sunusante\tests\Feature\Admin\StatistiquesTest.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
for ($i = 164; $i <= 175; $i++) {
    echo ($i+1) . ": " . $lines[$i] . "\n";
}