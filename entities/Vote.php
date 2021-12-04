<?php


abstract class Vote extends Entity {

    protected int $resourceID;
    protected int $userID;
    protected bool $voteType;


    public function __construct(int $ID, int $resourceID, int $userID, bool $voteType) {
        parent::__construct($ID);
        $this->resourceID = $resourceID;
        $this->userID = $userID;
        $this->voteType = $voteType;
    }

    public function changeVoteType(PDO $db): bool {
        $query = 'UPDATE ' . $this->table() . ' SET voteType = :voteType WHERE ID = ' . $this->ID;
        $result = $db->prepare($query);
        $newVoteType = !$this->voteType;
        $result->bindParam(':voteType', $newVoteType, PDO::PARAM_BOOL);
        $success = $result->execute();
        if ($success) {
            $this->voteType = $newVoteType;
        }
        return $success;
    }

    public function getVoteType(): bool {
        return $this->voteType;
    }

}
