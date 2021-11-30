<?php


abstract class LikableEntity extends Entity {

    protected array $likes;

    public function __construct(int $ID, string $likes) {
        parent::__construct($ID);
        $this->likes = json_decode($likes, true)['users'];
    }

    public function getLikes(): array {
        return $this->likes;
    }

    public abstract function likeBy(User $user);

}
