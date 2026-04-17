<?php
echo '__DIR__: ' . __DIR__ . '<br>';
echo 'Проверяем путь к конфигурации: ' . __DIR__ . '/../../app/config/database.php' . '<br>';
$fullPath = realpath(__DIR__ . '/../../app/config/database.php');
if ($fullPath) {
    echo 'Реальный путь: ' . $fullPath . '<br>';
    echo 'Файл существует<br>';
} else {
    echo 'Файл НЕ существует<br>';
}
?>