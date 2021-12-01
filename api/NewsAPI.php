<?php

class NewsAPI extends API implements Retrievable {

    public function __construct() {
        parent::__construct();
        $this->creator = new NewsCreator();
    }

    public function respond(string $methodName): void {
        switch ($methodName) {
            case 'get': {
                $this->get();
                return;
            }
            case 'getAll': {
                $this->getAll();
                return;
            }

            default: {
                http_response_code(405);
                echo(json_encode(['error'=>'Method is not supported']));
                die;
            }
        }
    }

    public function get(): void {

    }

    public function getAll(): void {

    }
}