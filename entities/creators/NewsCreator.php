<?php


class NewsCreator extends EntityCreator {

    protected function constructObject(array $row): News {
        return new News($row['ID'], $row['authorID'], $row['privacy'], $row['importance'], $row['title'],
            $row['content'], $row['publicationDate'], $row['views'], $row['rating'], $row['comments']);
    }

    public function newInstance(int $ID): ?News {
        $result = $this->db->prepare('SELECT * FROM ' . TABLE_NEWS . ' WHERE ID = :ID');
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
        $result = $this->db->query('SELECT * FROM ' . TABLE_NEWS);
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
