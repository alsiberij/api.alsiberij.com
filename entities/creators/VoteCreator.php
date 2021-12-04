<?php


abstract class VoteCreator extends EntityCreator {

    public function create(AssessableEntity $resource, User $user, bool $voteType): string {
        $vote = $this->newInstanceByResourceAndUser($resource, $user);
        if ($vote) {
            if ($vote->getVoteType() == $voteType) {
                try {
                    $this->db->beginTransaction();
                    $this->db->query('DELETE FROM ' . $this->table() . ' WHERE ID = ' . $resource->getID());
                    $user->decreaseVotes($this->db, $voteType);
                    $voteType ? $resource->downVote($this->db) : $resource->upVote($this->db);
                    $this->db->commit();
                } catch (PDOException $ex) {
                    $this->db->rollBack();
                    return 'Deleting unsuccessful';
                }
            } else {
                try {
                    $this->db->beginTransaction();
                    $vote->changeVoteType($this->db);
                    $user->increaseVotes($this->db, $voteType);
                    $user->decreaseVotes($this->db, !$voteType);
                    $resource->changeRating($this->db, $voteType ? $resource->getRating() + 2: $resource->getRating() - 2);
                    $this->db->commit();
                } catch (PDOException $ex) {
                    $this->db->rollBack();
                    return 'Changing vote type unsuccessful';
                }
            }
        } else {
            $resourceID = $resource->getID();
            $userID = $user->getID();
            try {
                $this->db->beginTransaction();
                $query = 'INSERT INTO ' . $this->table() . '(resourceID, userID, voteType) VALUES (:resource, :user, :type)';
                $result = $this->db->prepare($query);
                $result->bindParam(':resource', $resourceID, PDO::PARAM_INT);
                $result->bindParam(':user', $userID, PDO::PARAM_INT);
                $result->bindParam(':type', $voteType, PDO::PARAM_BOOL);
                $result->execute();
                $user->increaseVotes($this->db, $voteType);
                $voteType ? $resource->upVote($this->db) : $resource->downVote($this->db);
                $this->db->commit();
            } catch (PDOException $ex) {
                $this->db->rollBack();
                return 'Creating new vote unsuccessful';
            }
        }
        return '';
    }

    protected abstract function constructObject(array $row): Vote;

    public function newInstance(int $ID): ?Vote {
        return parent::newInstance($ID);
    }

    public function newInstanceByResourceAndUser(AssessableEntity $resource, User $user): ?Vote {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE resourceID = :resource AND userID = :user';
        $result = $this->db->prepare($query);
        $resourceID = $resource->getID();
        $result->bindParam(':resource', $resourceID);
        $userID = $user->getID();
        $result->bindParam(':user', $userID);
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
