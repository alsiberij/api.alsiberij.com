<?php


abstract class EntityCreator {

    protected PDO $db;

    public function __construct() {
        $this->db = DB::getConnection();
    }

    protected abstract function constructObject(array $row): Entity;

    public function newInstance(int $ID): ?Entity {
        $result = $this->db->prepare('SELECT * FROM ' . $this->table() . ' WHERE ID = :ID');
        $result->bindParam(':ID', $ID, PDO::PARAM_INT);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        if ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            return $this->constructObject($r);
        } else {
            return null;
        }
    }

    public function allInstances(): array {
        $result = $this->db->query('SELECT * FROM ' . $this->table());
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        $usersList = [];
        while ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            $usersList[] = $this->constructObject($r);
        }
        return $usersList;
    }

    public abstract function table(): string;

}
