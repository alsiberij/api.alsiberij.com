<?php

const ROOT = __DIR__ . '/';

spl_autoload_register(function(string $className): void {
    $folders = [

    ];

    foreach ($folders as $folder) {
        $path = ROOT . $folder . $className . '.php';
        if (is_file($path)) {
            require_once($path);
            break;
        }
    }
});

