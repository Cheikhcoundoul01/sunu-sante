<?php
$files = [
    'C:\Users\USER\Desktop\SunuSanté\sunusante\tests\Feature\Patient\FileAttenteTest.php',
    'C:\Users\USER\Desktop\SunuSanté\sunusante\tests\Feature\Medecin\PatientsDuJourTest.php',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = str_replace('Carbon::now()->addDay()->toDateString()', 'Carbon::now()->toDateString()', $content);
    file_put_contents($file, $content);
    echo "Fixed $file\n";
}
echo "Done\n";