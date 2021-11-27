<?php


class UserAPI extends API implements Retrievable, Creatable {

    public function __construct() {
        parent::__construct();
        $this->creator = new UserCreator();
    }

    public function respond(string $methodName): void {
        switch ($methodName) {
            case 'get': {
                $this->get();
                return;
            }
            case 'getAll': {
                $this->getAll();
                return;
            }
            case 'create': {
                $this->create();
                return;
            }

            default: {
                http_response_code(405);
                echo(json_encode(['error'=>'Method is not supported']));
                die;
            }
        }
    }

    public function get(): void {
        $rawUserIDs = $_POST['userIDs'] ?? $_GET['userIDs'] ?? null;
        $userIDs = json_decode($rawUserIDs);
        if (!$userIDs) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid user IDs. Use JSON notation']));
            die;
        }
        array_walk($userIDs, function (&$value, $key) {
            if (!is_int($value)) {
                http_response_code(400);
                echo(json_encode(['error' => "Invalid user ID ($value)"]));
                die;
            }
        });

        $usersList = [];
        foreach ($userIDs as $userID) {
            $user = $this->creator->newInstance($userID);
            if ($user) {
                $accessAllData = false;
                if ($this->authorizedUser &&
                    ($this->authorizedUser->getID() == $user->getID() || $this->authorizedUser->isAdministrator())) {
                    $accessAllData = true;
                }

                $user = $user->toArray();

                if ($accessAllData || ($user['activationStatus'] && $user['privacy'])) {
                    unset($user['activationToken']);
                    unset($user['password']);
                    unset($user['salt']);
                    unset($user['accessToken']);
                    unset($user['isActivated']);
                    if (!$accessAllData) {
                        if (!$user['emailPrivacy']) {
                            $user['email'] = null;
                        }
                        if (!$user['balancePrivacy']) {
                            $user['balance'] = null;
                        }
                        if (!$user['lastSeenTimePrivacy']) {
                            $user['lastSeenTime'] = null;
                        }
                    }
                    $usersList[] = $user;
                }
            }
        }

        http_response_code(200);
        echo(json_encode(['response'=>$usersList]));
    }

    public function getAll(): void {
        $usersList = $this->creator->allInstances();

        array_walk($usersList, function(User &$user, int $index): void {
            $accessAllData = false;
            if ($this->authorizedUser &&
                ($this->authorizedUser->getID() == $user->getID() || $this->authorizedUser->isAdministrator())) {
                $accessAllData = true;
            }

            $user = $user->toArray();

            if ($accessAllData || ($user['activationStatus'] && $user['privacy'])) {
                unset($user['activationToken']);
                unset($user['password']);
                unset($user['salt']);
                unset($user['accessToken']);
                unset($user['isActivated']);
                if (!$accessAllData) {
                    if (!$user['emailPrivacy']) {
                        $user['email'] = null;
                    }
                    if (!$user['balancePrivacy']) {
                        $user['balance'] = null;
                    }
                    if (!$user['lastSeenTimePrivacy']) {
                        $user['lastSeenTime'] = null;
                    }
                }
            } else {
                $user = null;
            }
        });

        $usersList = array_values(array_filter($usersList));

        http_response_code(200);
        echo(json_encode(['response'=>$usersList]));
    }

    public function create(): void {
        $nickname = $_POST['nickname'] ?? $_GET['nickname'] ?? '';
        $email = $_POST['email'] ?? $_GET['email'] ?? '';
        $password = $_POST['password'] ?? $_GET['password'] ?? '';

        $nicknameErrors = User::validateNickname($nickname);
        $emailErrors = User::validateEmail($email);
        $passwordErrors = User::validatePassword($password);

        $errors = array_merge($nicknameErrors, $emailErrors, $passwordErrors);
        if (!empty($errors)) {
            http_response_code(400);
            echo(json_encode(['errors'=>$errors]));
            die;
        }

        $salt = User::generateSalt();
        $saltMD5 = md5($salt);
        $password = md5(substr($saltMD5, 0, 16) . $password . substr($saltMD5, 16, 16));

        $query = 'INSERT INTO users (activationToken, nickname, email, password, salt) VALUES (:token, :nickname, :email, :password, :salt);';
        $result = $this->db->prepare($query);
        $activationToken = User::generateToken();
        $result->bindParam(':token', $activationToken);
        $result->bindParam(':nickname', $nickname);
        $result->bindParam(':email', $email);
        $result->bindParam(':password', $password);
        $result->bindParam(':salt', $salt);
        if (!$result->execute()) {
            http_response_code(400);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }

        $msg = "
                <!DOCTYPE html>
                <html lang='ru'>
                    Премногоуважаемый(ая) <b>$nickname</b>.<br>
                    Ваш токен активации $activationToken .
                </html>";
        $from = 'From: '. EMAIL . '\r\n';
        if (!mail($email, 'Регистрация', $msg, $from)) {
            http_response_code(500);
            echo(json_encode(['error' => 'Email can\'t be sent']));
            die;
        }

        http_response_code(200);
        echo(json_encode(['response' => 'Success']));
    }

    public function delete(): void {

    }
}