<?php


abstract class AssessableEntity extends Entity {

    protected int $rating;

    public function __construct(int $ID, int $voteRating) {
        parent::__construct($ID);
        $this->rating = $voteRating;
    }

    protected abstract function changeRating(int $newRating): bool;

    public final function upVote(): bool {
        return $this->changeRating($this->rating + 1);
    }

    public final function downVote(): bool {
        return $this->changeRating($this->rating - 1);
    }

}
