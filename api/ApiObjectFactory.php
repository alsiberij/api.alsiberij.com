<?php


class EntityApiFactory {

    public static function newInstance(string $entityName): ?API {
        switch ($entityName) {
            case 'users': return new UserAPI();

            default: return null;
        }
    }
}