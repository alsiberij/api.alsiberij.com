<?php


abstract class Entity {

    protected int $ID;
    protected PDO $db;


    public function __construct(int $ID) {
        $this->ID = $ID;
        $this->db = DB::getConnection();
    }

    public function getID(): int {
        return $this->ID;
    }

    public function toArray(): array {
        return ['ID' => $this->ID];
    }

}