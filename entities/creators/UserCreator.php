<?php


class UserCreator extends EntityCreator {

    public static function create(string $nickname, string $email, string $password):  string {
        $nicknameError = User::validateNickname($nickname);
        if ($nicknameError) {
            return $nicknameError;
        }
        $email = strtolower($email);
        $emailError = User::validateEmail($email, true);
        if ($emailError) {
            return $emailError;
        }
        $passwordError = User::validatePassword($password);
        if ($passwordError) {
            return $passwordError;
        }

        $query = 'INSERT INTO users (activationTokenHash, nickname, email, passwordHash, salt) VALUES (:activationToken, :nickname, :email, :password, :salt);';
        $result = DB::getConnection()->prepare($query);
        $activationToken = User::generateActivationToken();
        $activationTokenHash = User::calculateActivationTokenHash($activationToken);
        $result->bindParam(':activationToken', $activationTokenHash);
        $result->bindParam(':nickname', $nickname);
        $result->bindParam(':email', $email);
        $salt = User::generateSalt();
        $passwordHash = User::generatePasswordHash($password, $salt);
        $result->bindParam(':password', $passwordHash);
        $result->bindParam(':salt', $salt);
        if (!$result->execute()) {
            http_response_code(400);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }

        $msg = "
                <body>
                    <table>
                        <tr>
                        <td>Премногоуважаемый(ая) <b>$nickname</b> <br>Ваш токен активации аккаунта $activationToken .</td>
                        </tr> 
                    </table>
                </body>
        ";
        $from = 'From: '. EMAIL . '\r\n';
        if (!mail($email, 'Регистрация', $msg, $from)) {
            http_response_code(500);
            echo(json_encode(['error' => 'Email can\'t be sent']));
            die;
        }
        return '';
    }

    protected function constructObject(array $row): User {
        return new User($row['ID'], $row['accessToken'], $row['accessTokenExpiration'], $row['activationStatus'],
            $row['activationTokenHash'], $row['administrator'], $row['moderator'], $row['privacy'],
            $row['nickname'], $row['email'], $row['emailPrivacy'], $row['passwordHash'], $row['salt'],
            $row['registrationDate'], $row['balance'], $row['balancePrivacy'], $row['avatar'], $row['birthday'],
            $row['location'], $row['bio'], $row['upVotes'], $row['downVotes'], $row['comments'], $row['paidOrders'],
            $row['lastSeenTime'], $row['lastSeenTimePrivacy']);
    }

    public function newInstance(int $ID): ?User {
        return parent::newInstance($ID);
    }

    public function table(): string {
        return TABLE_USER;
    }

    public function newInstanceByAccessToken(string $tokenHash): ?User {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE accessToken = :token';
        $result = $this->db->prepare($query);
        $result->bindParam(':token', $tokenHash);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        if ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            return $this->constructObject($r);
        } else {
            return null;
        }
    }

    public function newInstanceByActivationToken(string $tokenHash): ?User {
        $query = 'SELECT * FROM ' . $this->table() . ' WHERE activationTokenHash = :token';
        $result = $this->db->prepare($query);
        $result->bindParam(':token', $tokenHash);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        if ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            return $this->constructObject($r);
        } else {
            return null;
        }
    }

    public function newInstanceByEmail(string $email): ?User {
        $result = $this->db->prepare('SELECT * FROM ' . $this->table() . ' WHERE email = :email');
        $result->bindParam(':email', $email);
        if (!$result->execute()) {
            http_response_code(500);
            echo(json_encode(['error'=>'Internal DB error']));
            die;
        }
        if ($r = $result->fetch(PDO::FETCH_ASSOC)) {
            return $this->constructObject($r);
        } else {
            return null;
        }
    }

}
