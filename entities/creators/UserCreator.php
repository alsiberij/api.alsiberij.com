<?php


class UserCreator extends EntityCreator {

    public function newInstance(int $ID): ?User {
        $result = $this->db->prepare('SELECT * FROM ' . TABLE_USER . ' WHERE ID = :ID');
        $result->bindParam(':ID', $ID, PDO::PARAM_INT);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        if ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            return new User($r['ID'], $r['activationStatus'], $r['activationToken'], $r['accessToken'], $r['administrator'], $r['moderator'],
                $r['privacy'], $r['nickname'], $r['email'], $r['emailPrivacy'], $r['password'], $r['salt'],
                $r['registrationDate'], $r['balance'], $r['balancePrivacy'], $r['avatar'], $r['birthday'],
                $r['location'], $r['bio'], $r['likes'], $r['comments'], $r['paidOrders'], $r['lastSeenTime'],
                $r['lastSeenTimePrivacy']);
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
            $usersList[] = new User($r['ID'], $r['activationStatus'], $r['activationToken'], $r['accessToken'], $r['administrator'], $r['moderator'],
                $r['privacy'], $r['nickname'], $r['email'], $r['emailPrivacy'], $r['password'], $r['salt'],
                $r['registrationDate'], $r['balance'], $r['balancePrivacy'], $r['avatar'], $r['birthday'],
                $r['location'], $r['bio'], $r['likes'], $r['comments'], $r['paidOrders'], $r['lastSeenTime'],
                $r['lastSeenTimePrivacy']);
        }
        return $usersList;
    }

}