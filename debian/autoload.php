<?php

require_once '/usr/share/php/Composer/InstalledVersions.php';
require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/AbraFlexiBricks/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'AbraFlexi\\Reminder\\';
    if (str_starts_with($class, $prefix)) {
        $file = '/usr/lib/abraflexi-reminder/Reminder/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

(function (): void {
    $versions = [];
    foreach (\Composer\InstalledVersions::getAllRawData() as $d) {
        $versions = array_merge($versions, $d['versions'] ?? []);
    }
    $name    = 'unknown';
    $version = '0.0.0';
    $versions[$name] = ['pretty_version' => $version, 'version' => $version,
        'reference' => null, 'type' => 'library', 'install_path' => __DIR__,
        'aliases' => [], 'dev_requirement' => false];
    \Composer\InstalledVersions::reload([
        'root' => ['name' => $name, 'pretty_version' => $version, 'version' => $version,
            'reference' => null, 'type' => 'library', 'install_path' => __DIR__,
            'aliases' => [], 'dev' => false],
        'versions' => $versions,
    ]);
})();
