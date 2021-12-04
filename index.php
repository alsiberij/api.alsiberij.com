<?php

const ACCESS_TOKEN_LIFETIME = 60 * 24;

const ROOT = __DIR__ . '/';
const EMAIL = 'ceo@alsiberij.com';

const TABLE_USER = 'users';
const TABLE_NEWS = 'news';
const TABLE_NEWS_VOTE = 'news_votes';

spl_autoload_register(function(string $className): void {
    $folders = [
        'api/',
        'api/interfaces/',

        'entities/',
        'entities/creators/',

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

header('Content-type: application/json');

if (isset($_COOKIE['PHPSESSID'])) {
    setcookie('PHPSESSID', '', time() - 1, '/');
}
unset($_COOKIE['PHPSESSID']);

if (isset($_POST['session']) || isset($_GET['session'])) {
    session_id($_POST['session'] ?? $_GET['session']);
    session_start();
}

$entityAndMethod = explode('/', trim(explode('?', $_SERVER['REQUEST_URI'])[0], '/'));
if (count($entityAndMethod) != 2) {
    http_response_code(400);
    echo(json_encode(['error' => 'Invalid request. Try entity/method?params pattern'], JSON_UNESCAPED_SLASHES));
    die;
}

[$entityName, $methodName] = $entityAndMethod;
$entityName = strtolower($entityName);

$entity = ApiObjectFactory::newInstance($entityName);
if (!$entity) {
    http_response_code(400);
    echo(json_encode(['error' => 'No such entity']));
    die;
}

$entity->respond($methodName);