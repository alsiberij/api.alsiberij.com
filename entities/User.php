<?php


class User extends Entity {

    protected bool $isActivated;
    protected string $activationTokenHash;
    protected ?string $accessTokenHash;
    protected bool $isAdministrator;
    protected bool $isModerator;
    protected bool $privacy;
    protected string $nickname;
    protected string $email;
    protected bool $emailPrivacy;
    protected string $passwordHash;
    protected string $salt;
    protected DateTime $registrationDate;
    protected int $balance;
    protected bool $balancePrivacy;
    protected bool $avatar;
    protected ?DateTime $birthday;
    protected ?string $location;
    protected ?string $bio;
    protected int $likes;
    protected int $comments;
    protected int $paidOrders;
    protected DateTime $lastSeenTime;
    protected bool $lastSeenTimePrivacy;

    public const ACTIVATION_TOKEN_LENGTH = 32;
    public const ACTIVATION_TOKEN_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

    public const ACCESS_TOKEN_LENGTH = 32;
    public const ACCESS_TOKEN_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

    public const NICKNAME_MIN_LENGTH = 3;
    public const NICKNAME_MAX_LENGTH = 32;
    public const NICKNAME_PATTERN = '~^[a-zA-Zа-яА-ЯёЁ0-9]+$~u';

    public const EMAIL_MIN_LENGTH = 10;
    public const EMAIL_MAX_LENGTH = 32;
    public const EMAIL_PATTERN = '~^[\.a-z0-9-]+@[\.a-z0-9-]+$~';

    public const PASSWORD_MIN_LENGTH = 6;
    public const PASSWORD_MAX_LENGTH = 32;
    public const PASSWORD_PATTERN = '~^(?=.*[0-9])[a-zA-Z0-9]+$~';

    public const SALT_LENGTH = 8;
    public const SALT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';


    public static function validateNickname(string $nickname): array {
        $errors = [];
        if (mb_strlen($nickname) < self::NICKNAME_MIN_LENGTH || mb_strlen($nickname > self::NICKNAME_MAX_LENGTH)) {
            $errors['nickname'] = 'Invalid length. Allowed length '. self::NICKNAME_MIN_LENGTH . '-' . self::NICKNAME_MAX_LENGTH;
        } elseif (!preg_match(self::NICKNAME_PATTERN, $nickname)) {
            $errors['nickname'] = 'Invalid nickname. Special characters are not allowed';
        }
        return $errors;
    }

    public static function validateEmail(string $email, bool $checkExistence): array {
        $errors = [];
        if (mb_strlen($email) < self::EMAIL_MIN_LENGTH || mb_strlen($email > self::EMAIL_MAX_LENGTH)) {
            $errors['email'] = 'Invalid length. Allowed length '. self::EMAIL_MIN_LENGTH . '-' . self::EMAIL_MAX_LENGTH;
        } elseif (!preg_match(self::EMAIL_PATTERN, $email)) {
            $errors['email'] = 'Invalid email';
        } elseif ($checkExistence) {
            $result = DB::getConnection()->prepare('SELECT ID FROM ' . TABLE_USER . ' WHERE email = :email');
            $result->bindParam(':email', $email);
            if (!$result->execute()) {
                http_response_code(500);
                echo(json_encode(['error' => 'Query can not be executed']));
                die;
            }
            if ($result->fetch(PDO::FETCH_ASSOC)) {
                $errors['email'] = 'Email already exists';
            }
        }
        return $errors;
    }

    public static function validatePassword(string $password): array {
        $errors = [];
        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH || mb_strlen($password) > self::PASSWORD_MAX_LENGTH) {
            $errors['password'] = 'Invalid length. Allowed length '. self::PASSWORD_MIN_LENGTH . '-' . self::PASSWORD_MAX_LENGTH;
        } elseif (!preg_match(self::PASSWORD_PATTERN, $password)) {
            $errors['password'] = 'Invalid password. Password should have at least 1 letter and 1 digit';
        }
        return $errors;
    }

    public static function generatePasswordHash(string $password, string $salt): string {
        $saltMD5 = md5($salt);
        return md5(substr($saltMD5, 0, 16) . $password . substr($saltMD5, 16, 16));
    }

    private static function validateSalt(string $salt): bool {
        $result = DB::getConnection()->prepare('SELECT ID FROM users WHERE salt = :salt');
        $result->bindParam(':salt', $salt);
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

    public static function generateSalt(): string {
        do {
            $salt = '';
            for ($i = 0; $i < self::SALT_LENGTH; $i++) {
                $salt .= self::SALT_ALPHABET[rand(0, mb_strlen(self::SALT_ALPHABET) - 1)];
            }
        } while (!self::validateSalt($salt));
        return $salt;
    }

    private static function validateActivationToken(string $token): bool {
        $tokenHash = self::calculateActivationTokenHash($token);
        $result = DB::getConnection()->prepare('SELECT ID FROM users WHERE activationTokenHash = :token');
        $result->bindParam(':token', $tokenHash);
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

    public static function generateActivationToken(): string {
        do {
            $token = '';
            for ($i = 0; $i < self::ACTIVATION_TOKEN_LENGTH; $i++) {
                $token .= self::ACTIVATION_TOKEN_ALPHABET[rand(0, mb_strlen(self::ACTIVATION_TOKEN_ALPHABET) - 1)];
            }
        } while (!self::validateActivationToken($token));
        return $token;
    }

    public static function calculateActivationTokenHash(string $activationToken): string {
        $tokenMD5 = md5($activationToken);
        return md5(substr($tokenMD5, 0, 16) . $activationToken . substr($tokenMD5, 16, 16));
    }

    public static function validateAccessTokenHash(string $token): bool {
        $tokenHash = self::calculateAccessTokenHash($token);
        $result = DB::getConnection()->prepare('SELECT ID FROM users WHERE accessTokenHash = :token');
        $result->bindParam(':token', $tokenHash);
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

    public static function generateAccessToken(): string {
        do {
            $token = '';
            for ($i = 0; $i < self::ACCESS_TOKEN_LENGTH; $i++) {
                $token .= self::ACCESS_TOKEN_ALPHABET[rand(0, mb_strlen(self::ACCESS_TOKEN_ALPHABET) - 1)];
            }
        } while (!self::validateAccessTokenHash($token));
        return $token;
    }

    public static function calculateAccessTokenHash(string $accessToken): string {
        return md5(md5($accessToken) . md5(md5($accessToken)));
    }

    public function __construct(int $ID, bool $isActivated, string $activationTokenHash, ?string $accessTokenHash,
                                bool $isAdmin, bool $contentCreator, bool $privacy, string $nickname, string $email,
                                bool $emailPrivacy, string $passwordHash, string  $salt, string $registrationDate,
                                int $balance, bool $balancePrivacy, bool $avatar, ?string $birthday, ?string $location,
                                ?string $bio, int $likes, int $comments, int $paidOrders, string $lastSeenTime,
                                bool $lastSeenTimePrivacy) {
        parent::__construct($ID);
        $this->isActivated = $isActivated;
        $this->activationTokenHash = $activationTokenHash;
        $this->accessTokenHash = $accessTokenHash;
        $this->isAdministrator = $isAdmin;
        $this->isModerator = $contentCreator;
        $this->privacy = $privacy;
        $this->nickname = $nickname;
        $this->email = $email;
        $this->emailPrivacy = $emailPrivacy;
        $this->passwordHash = $passwordHash;
        $this->salt = $salt;
        try {
            $this->registrationDate = new DateTime($registrationDate);
        } catch (Exception $ex) {
            $this->registrationDate = new DateTime();
        }
        $this->balance = $balance;
        $this->balancePrivacy = $balancePrivacy;
        $this->avatar = $avatar;
        if (!$birthday) {
            $this->birthday = null;
        } else {
            try {
                $this->birthday = new DateTime($birthday);
            } catch (Exception $ex) {
                $this->birthday = null;
            }
        }
        $this->location = $location;
        $this->bio = $bio;
        $this->likes = $likes;
        $this->comments = $comments;
        $this->paidOrders = $paidOrders;
        try {
            $this->lastSeenTime = new DateTime($lastSeenTime);
        } catch (Exception $ex) {
            $this->lastSeenTime = new DateTime();
        }
        $this->lastSeenTimePrivacy = $lastSeenTimePrivacy;
    }

    public function toArray(): array {
        return array_merge(parent::toArray(), [
            'ID' => $this->ID,
            'activationStatus' => $this->isActivated,
            'activationTokenHash' => $this->activationTokenHash,
            'accessTokenHash' => $this->accessTokenHash,
            'isAdministrator' => $this->isAdministrator,
            'isModerator' => $this->isModerator,
            'privacy' => $this->privacy,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'emailPrivacy' => $this->emailPrivacy,
            'passwordHash' => $this->passwordHash,
            'salt' => $this->salt,
            'registrationDate' => $this->registrationDate->format('Y.m.d H:i:s'),
            'balance' => $this->balance,
            'balancePrivacy' => $this->balancePrivacy,
            'avatar' => $this->avatar,
            'birthday' => $this->birthday ? $this->birthday->format('Y.m.d H:i:s') : null,
            'location' => $this->location,
            'bio' => $this->bio,
            'likes' => $this->likes,
            'comments' => $this->comments,
            'paidOrders' => $this->paidOrders,
            'lastSeenTime' => $this->lastSeenTime->format('Y.m.d H:i:s'),
            'lastSeenTimePrivacy' => $this->lastSeenTimePrivacy,
        ]);
    }

    public function getPrivacy(): bool {
        return $this->privacy;
    }

    public function getEmailPrivacy(): bool {
        return $this->emailPrivacy;
    }

    public function getBalancePrivacy(): bool {
        return $this->balancePrivacy;
    }

    public function isAdministrator(): bool {
        return $this->isAdministrator;
    }

    public function isModerator(): bool {
        return $this->isModerator;
    }

    public function getNickname(): string {
        return $this->nickname;
    }

    public function getSalt(): string {
        return $this->salt;
    }

    public function getDeletionToken(): string {
        return substr(md5('DELETE' . $this->salt . 'DELETE'), 8, 16);
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function isActivated(): bool {
        return $this->isActivated;
    }

    public function activate(): bool {
        if (!$this->isActivated) {
            if ($this->db->query('UPDATE ' . TABLE_USER . ' SET activationStatus = 1 WHERE ID = ' . $this->ID)) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function getPasswordHash(): string {
        return $this->passwordHash;
    }

}