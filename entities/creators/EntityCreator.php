<?php


abstract class EntityCreator {

    protected PDO $db;

    public function __construct() {
        $this->db = DB::getConnection();
    }

    public abstract function newInstance(): ?Entity;
}