<?php


class UserCreator extends EntityCreator {

    protected function constructObject(array $row): User {
        return new User($row['ID'], $row['activationStatus'], $row['activationToken'], $row['accessToken'],
            $row['administrator'], $row['moderator'], $row['privacy'], $row['nickname'], $row['email'],
            $row['emailPrivacy'], $row['password'], $row['salt'], $row['registrationDate'], $row['balance'],
            $row['balancePrivacy'], $row['avatar'], $row['birthday'], $row['location'], $row['bio'], $row['likes'],
            $row['comments'], $row['paidOrders'], $row['lastSeenTime'], $row['lastSeenTimePrivacy']);
    }

    public function newInstanceByAccessToken(string $accessToken) {
        $result = $this->db->prepare('SELECT * FROM ' . TABLE_USER . ' WHERE accessToken = :token');
        $result->bindParam(':token', $accessToken);
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

    public function newInstance(int $ID): ?User {
        $result = $this->db->prepare('SELECT * FROM ' . TABLE_USER . ' WHERE ID = :ID');
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
        $result = $this->db->query('SELECT * FROM ' . TABLE_USER);
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