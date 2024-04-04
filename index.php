<?php

require_once 'Core/Backup.php';

$backup = new Backup('your_table');

echo '<pre>';
$backup->backup();
echo '</pre>';

?>