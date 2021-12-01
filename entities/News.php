<?php


class News extends AssessableEntity {

    protected int $authorID;
    protected bool $privacy;
    protected bool $isImportant;
    protected string $title;
    protected string $content;
    protected DateTime $publicationDate;
    protected int $views;
    protected int $comments;


    public function __construct(int    $ID, int $authorID, bool $privacy, bool $isImportant, string $title,
                                string $content, string $publicationDate, int $views, string $rating, int $comments) {
        parent::__construct($ID, $rating);
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

    public function table(): string {
        return TABLE_NEWS;
    }

    public function toArray(): array {
        return array_merge(parent::toArray(), [
            'authorID' => $this->authorID,
            'privacy' => $this->privacy,
            'importance' => $this->isImportant,
            'title' => $this->title,
            'content' => $this->content,
            'publicationDate' => $this->publicationDate->format('Y.m.d H:i:s'),
            'views' => $this->views,
            'comments' => $this->comments
        ]);
    }

    public function changeRating(int $newRating): bool {
        $query = 'UPDATE ' . $this->table() . ' SET rating = :rating WHERE ID = ' . $this->ID ;
        $result = $this->db->prepare($query);
        $result->bindParam(':rating', $newRating, PDO::PARAM_INT);
        $success = $result->execute();
        if ($success) {
            $this->rating = $newRating;
        }
        return $success;
    }

    public function getPrivacy(): bool {
        return $this->privacy;
    }

    public function getAuthorID(): int {
        return $this->authorID;
    }

}
