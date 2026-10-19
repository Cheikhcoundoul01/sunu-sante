<?php
$file = 'C:\Users\USER\Desktop\SunuSanté\sunusante\tests\Feature\Accueil\FileDuJourTest.php';
$content = file_get_contents($file);
$content = str_replace('Carbon::now()->addDay()->toDateString()', 'Carbon::now()->toDateString()', $content);
file_put_contents($file, $content);
echo "Done\n";