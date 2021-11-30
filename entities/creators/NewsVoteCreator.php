<?php

class NewsVoteCreator extends EntityCreator {

    protected function constructObject(array $row): NewsVote {
        return new NewsVote($row['ID'], $row['resourceID'], $row['userID'], $row['voteType']);
    }

    public function newInstance(int $ID): ?NewsVote {
        return parent::newInstance($ID);
    }

    public function table(): string {
        return TABLE_NEWS_VOTE;
    }

}
