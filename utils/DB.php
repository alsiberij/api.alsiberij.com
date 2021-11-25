<?php


class DB {

    public static function getConnection(): ?PDO {
        $params = require(ROOT . 'config/database.php');
        try {
            $dsn = "mysql:host={$params['host']};dbname={$params['dbname']}";
            return new PDO($dsn, $params['user'], $params['password']);
        } catch (PDOException $ex) {
            header('Content-type: application/json');
            http_response_code(500);
            echo(json_encode(['error' => 'Unable to connect database.']));
            die;
        }
    }

}