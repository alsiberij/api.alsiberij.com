<?php


class EntityApiFactory {

    public static function newInstance(string $entityName): ?API {
        switch ($entityName) {


            default: return null;
        }
    }
}