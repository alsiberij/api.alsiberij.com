<?php

const ROOT = __DIR__ . '/';

const TABLE_USER = 'users';

spl_autoload_register(function(string $className): void {
    $folders = [
        'api/',
        'api/interfaces/',

        'utils/'
    ];

    foreach ($folders as $folder) {
        $path = ROOT . $folder . $className . '.php';
        if (is_file($path)) {
            require_once($path);
            break;
        }
    }
});

