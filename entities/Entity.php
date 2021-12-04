<?php


abstract class Entity {

    protected int $ID;


    public function __construct(int $ID) {
        $this->ID = $ID;
    }

    public function getID(): int {
        return $this->ID;
    }

    public function toArray(): array {
        return ['ID' => $this->ID];
    }

    public abstract function table(): string;

}