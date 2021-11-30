<?php


class CreatorFactory {

    public static function getCreator(string $entityName): ?EntityCreator {
        switch ($entityName) {
            case 'news': return new NewsCreator();
            case 'user': return new UserCreator();

            default: return null;
        }
    }

}
