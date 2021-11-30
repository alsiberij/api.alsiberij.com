<?php


class NewsCreator extends EntityCreator {

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
