<?php


interface Authenticatable {

    public function authenticate(): void;

    public function refreshToken(): void;

    public function revokeToken(): void;

}