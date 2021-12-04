<?php


abstract class AssessableEntity extends Entity {

    protected int $rating;

    public function __construct(int $ID, int $voteRating) {
        parent::__construct($ID);
        $this->rating = $voteRating;
    }

    public function toArray(): array {
        return array_merge(parent::toArray(), [
            'rating' => $this->rating
        ]);
    }

    public abstract function changeRating(PDO $db, int $newRating): bool;

    public final function upVote(PDO $db): bool {
        return $this->changeRating($db, $this->rating + 1);
    }

    public final function downVote(PDO $db): bool {
        return $this->changeRating($db, $this->rating - 1);
    }

    public function getRating(): int {
        return $this->rating;
    }

}
