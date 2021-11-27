<?php


class Activation extends Entity {

    protected bool $status;
    protected string $token;

    protected const TOKEN_LENGTH = 32;
    public const TOKEN_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-#*';


    private static function validateToken(string $token): bool {
        $result = DB::getConnection()->prepare('SELECT userID FROM activations WHERE token = :token');
        $result->bindParam(':token', $token);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }
        if ($result->fetch(PDO::FETCH_ASSOC)) {
            return false;
        } else {
            return true;
        }
    }

    public static function generateToken(): string {
        do {
            $token = '';
            for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
                $token .= self::TOKEN_ALPHABET[rand(0, mb_strlen(self::TOKEN_ALPHABET) - 1)];
            }
        } while (!self::validateToken($token));
        return $token;
    }


    public function __construct(int $ID, bool $status, string $token) {
        parent::__construct($ID);
        $this->status = $status;
        $this->token = $token;
    }

}