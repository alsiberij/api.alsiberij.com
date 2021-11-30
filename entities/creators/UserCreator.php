<?php


class UserCreator extends EntityCreator {

    protected function constructObject(array $row): User {
        return new User($row['ID'], $row['accessToken'], $row['accessTokenExpiration'], $row['activationStatus'],
            $row['activationTokenHash'], $row['administrator'], $row['moderator'], $row['privacy'],
            $row['nickname'], $row['email'], $row['emailPrivacy'], $row['passwordHash'], $row['salt'],
            $row['registrationDate'], $row['balance'], $row['balancePrivacy'], $row['avatar'], $row['birthday'],
            $row['location'], $row['bio'], $row['upVotes'], $row['downVotes'], $row['comments'], $row['paidOrders'],
            $row['lastSeenTime'], $row['lastSeenTimePrivacy']);
    }

    public function newInstance(int $ID): ?User {
        return parent::newInstance($ID);
    }

    public function table(): string {
        return TABLE_USER;
    }

    public function newInstanceByAccessToken(string $tokenHash): ?User {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE accessToken = :token';
        $result = $this->db->prepare($query);
        $result->bindParam(':token', $tokenHash);
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

    public function newInstanceByActivationToken(string $tokenHash): ?User {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE activationTokenHash = :token';
        $result = $this->db->prepare($query);
        $result->bindParam(':token', $tokenHash);
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

    public function newInstanceByEmail(string $email): ?User {
        $result = $this->db->prepare('SELECT * FROM ' . $this->table() . ' WHERE email = :email');
        $result->bindParam(':email', $email);
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

}
