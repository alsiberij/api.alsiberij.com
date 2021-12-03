<?php


class NewsCreator extends EntityCreator {

    public function create(User $author, bool $privacy, bool $importance, string $title, string $content): string {
        $titleError = News::validateTitle($title);
        if ($titleError) {
            return $titleError;
        }
        $contentError = News::validateContent($content);
        if ($contentError) {
            return $contentError;
        }

        $query = 'INSERT INTO ' . $this->table() . '(authorID, privacy, importance, title, content) VALUES (:author, :privacy, :importance, :title, :content);';
        $result = $this->db->prepare($query);
        $authorID = $author->getID();
        $result->bindParam(':author', $authorID, PDO::PARAM_INT);
        $result->bindParam(':privacy', $privacy, PDO::PARAM_BOOL);
        $result->bindParam(':importance', $importance, PDO::PARAM_BOOL);
        $result->bindParam(':title', $title);
        $result->bindParam(':content', $content);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }
        return '';
    }

    protected function constructObject(array $row): News {
        return new News($row['ID'], $row['authorID'], $row['privacy'], $row['importance'], $row['title'],
            $row['content'], $row['publicationDate'], $row['views'], $row['rating'], $row['comments']);
    }

    public function newInstance(int $ID): ?News {
        return parent::newInstance($ID);
    }

    public function table(): string {
        return TABLE_NEWS;
    }

}
