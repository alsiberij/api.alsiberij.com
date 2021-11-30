<?php


abstract class VoteCreator extends EntityCreator {

    public function newInstance(int $ID): ?Vote {
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

}
