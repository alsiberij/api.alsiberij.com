<?php


class News extends LikableEntity {

    protected int $authorID;
    protected bool $privacy;
    protected bool $isImportant;
    protected string $title;
    protected string $content;
    protected DateTime $publicationDate;
    protected int $views;
    protected int $comments;


    public function __construct(int $ID, int $authorID, bool $privacy, bool $isImportant, string $title,
                                string $content, string $publicationDate, int $views, string $likes, int $comments) {
        parent::__construct($ID, $likes);
        $this->authorID = $authorID;
        $this->privacy = $privacy;
        $this->isImportant = $isImportant;
        $this->title = $title;
        $this->content = $content;
        try {
            $this->publicationDate = new DateTime($publicationDate);
        } catch (Exception $ex) {
            $this->publicationDate = new DateTime();
        }
        $this->views = $views;
        $this->comments = $comments;
    }

    public function likeBy(User $user) {

    }
}
