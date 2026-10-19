<?php
$file = 'C:\Users\USER\Desktop\SunuSanté\sunusante\tests\Feature\Admin\StatistiquesTest.php';
$content = file_get_contents($file);
$content = str_replace(
    "\$this->disponibilite(\$medecinB->id, \$serviceB->id, \$jour, '09:00', '12:00);",
    "\$this->disponibilite(\$medecinB->id, \$serviceB->id, \$jour, '09:00', '12:00');",
    $content
);
file_put_contents($file, $content);
echo "Fixed\n";