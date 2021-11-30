<?php


abstract class AssessableEntity extends Entity {

    protected int $rating;

    public function __construct(int $ID, int $voteRating) {
        parent::__construct($ID);
        $this->rating = $voteRating;
    }

    protected abstract function changeRating(int $newRating);

    public final function upVote() {
        $this->changeRating($this->rating + 1);
    }

    public final function downVote() {
        $this->changeRating($this->rating - 1);
    }

}
