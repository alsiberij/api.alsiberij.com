<?php


abstract class EntityCreator {

    protected PDO $db;

    public function __construct() {
        $this->db = DB::getConnection();
    }

    protected abstract function constructObject(array $row): Entity;

    public abstract function newInstance(int $ID): ?Entity;

    public abstract function allInstances(): array;

    public abstract function table(): string;

}
