<?php

class ActivationCreator extends EntityCreator {

    public function newInstance(int $ID): ?Activation {
        $result = $this->db->prepare('SELECT * FROM ' . TABLE_ACTIVATION . ' WHERE userID = :ID');
        $result->bindParam(':ID', $ID, PDO::PARAM_INT);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        if ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            return new Activation($r['userID'], $r['activationStatus'], $r['activationToken']);
        } else {
            return null;
        }
    }

    public function allInstances(): array {
        $result = $this->db->query('SELECT * FROM ' . TABLE_ACTIVATION);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        $activationsList = [];
        while ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            $activationsList[] = new Activation($r['userID'], $r['activationStatus'], $r['activationToken']);
        }
        return $activationsList;
    }
}