<?php


abstract class VoteCreator extends EntityCreator {

    public function create(AssessableEntity $resource, User $user, bool $voteType): bool {
        $vote = $this->newInstanceByResourceAndUser($resource, $user);
        if ($vote) {
            if ($vote->getVoteType() == $voteType) {
                $this->delete($vote);
                $s = $user->decreaseVotes($voteType);
                $s = $s && ($voteType ? $resource->downVote() : $resource->upVote());
            } else {
                $s = $vote->changeVoteType();
                $s = $s && $user->increaseVotes($voteType);
                $s = $s && $user->decreaseVotes(!$voteType);
                $s = $s && $resource->changeRating($voteType ? $resource->getRating() + 2: $resource->getRating() - 2);
            }
        } else {
            $resourceID = $resource->getID();
            $userID = $user->getID();

            $query = 'INSERT INTO ' . $this->table() . '(resourceID, userID, voteType) VALUES (:resource, :user, :type)';
            $result = $this->db->prepare($query);
            $result->bindParam(':resource', $resourceID, PDO::PARAM_INT);
            $result->bindParam(':user', $userID, PDO::PARAM_INT);
            $result->bindParam(':type', $voteType, PDO::PARAM_BOOL);
            if (!$result->execute()) {
                http_response_code(500);
                echo(json_encode(['error' => 'Query can not be executed']));
                die;
            }
            $s = $user->increaseVotes($voteType);
            $s = $s && ($voteType ? $resource->upVote() : $resource->downVote());
        }
        return $s;
    }

    public function delete(Vote $vote): void {
        $query = 'DELETE FROM ' . $this->table() . ' WHERE ID = :ID';
        $result = $this->db->prepare($query);
        $voteID = $vote->getID();
        $result->bindParam(':ID', $voteID);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }
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
