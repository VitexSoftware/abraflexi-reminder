<?php
// Debian autoloader for abraflexi-reminder
require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/AbraFlexiBricks/autoload.php';
// PSR-4 autoloader for application classes
spl_autoload_register(function (string $class): void {
    if (strncmp('AbraFlexi\\Reminder\\', $class, 19) === 0) {
        $file = '/usr/lib/abraflexi-reminder/Reminder/' . str_replace('\\', '/', substr($class, 19)) . '.php';
        if (file_exists($file)) { require $file; }
    }
});
