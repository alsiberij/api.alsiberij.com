<?php


class UserAPI extends API implements Retrievable, Creatable, Activatable, Authenticatable {

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
            case 'delete': {
                $this->delete();
                return;
            }
            case 'activate': {
                $this->activate();
                return;
            }
            case 'auth': {
                $this->authenticate();
                return;
            }


            default: {
                http_response_code(405);
                echo(json_encode(['error'=>'Method is not supported']));
                die;
            }
        }
    }

    private function handleUserData(array &$user, bool $accessAllAvailableData): void {
        unset($user['activationTokenHash']);
        unset($user['passwordHash']);
        unset($user['salt']);
        unset($user['accessTokenHash']);
        unset($user['isActivated']);
        if (!$accessAllAvailableData) {
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
    }

    public function get(): void {
        $rawUserIDs = $_POST['userIDs'] ?? $_GET['userIDs'] ?? null;
        if (!$rawUserIDs) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameter: userIDs']));
            die;
        }
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
                    $this->handleUserData($user, $accessAllData);
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
                $this->handleUserData($user, $accessAllData);
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
        $emailErrors = User::validateEmail($email, true);
        $passwordErrors = User::validatePassword($password);

        $errors = array_merge($nicknameErrors, $emailErrors, $passwordErrors);
        if (!empty($errors)) {
            http_response_code(400);
            echo(json_encode(['errors'=>$errors]));
            die;
        }

        $salt = User::generateSalt();
        $passwordHash = User::generatePasswordHash($password, $salt);

        $query = 'INSERT INTO users (activationTokenHash, accessTokenHash, nickname, email, passwordHash, salt) VALUES (:activationToken, :accessToken, :nickname, :email, :password, :salt);';
        $result = $this->db->prepare($query);
        $activationToken = User::generateActivationToken();
        $activationTokenHash = User::calculateActivationTokenHash($activationToken);
        $result->bindParam(':activationToken', $activationTokenHash);
        $accessToken = User::generateAccessToken();
        $accessTokenHash = User::calculateAccessTokenHash($accessToken);
        $result->bindParam(':accessToken', $accessTokenHash);
        $result->bindParam(':nickname', $nickname);
        $result->bindParam(':email', $email);
        $result->bindParam(':password', $passwordHash);
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
                    Ваш токен активации аккаунта $activationToken .
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
        if (!$this->authorizedUser) {
            http_response_code(403);
            echo(json_encode(['error' => 'Unauthorized']));
            die;
        }

        $attempt = $_POST['attempt'] ?? $_GET['attempt'] ?? null;
        if (!$attempt) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameter: attempt']));
            die;
        }

        if ($attempt == 'request') {
            $msg = '
                <!DOCTYPE html>
                <html lang=\'ru\'>
                    Премногоуважаемый(ая) <b>' . $this->authorizedUser->getNickname() . '</b>.<br>
                    Ваш токен удаления аккаунта ' . $this->authorizedUser->getDeletionToken() . ' .
                </html>';
            $from = 'From: '. EMAIL . '\r\n';
            if (!mail($this->authorizedUser->getEmail(), 'Удаление аккаунта', $msg, $from)) {
                http_response_code(500);
                echo(json_encode(['error' => 'Email can\'t be sent']));
                die;
            }
            http_response_code(200);
            echo(json_encode(['response' => 'Success']));
        } elseif ($attempt == 'process') {
            $deleteToken = $_POST['deleteToken'] ?? $_GET['deleteToken'] ?? null;
            if (!$deleteToken) {
                http_response_code(400);
                echo(json_encode(['error' => 'Missing parameter: deleteToken']));
                die;
            }
            if ($this->authorizedUser->getDeletionToken() != $deleteToken) {
                http_response_code(400);
                echo(json_encode(['error' => 'Invalid delete token']));
                die;
            }
            $query = 'DELETE FROM users WHERE ID = :ID';
            $result = $this->db->prepare($query);
            $userID = $this->authorizedUser->getID();
            $result->bindParam(':ID', $userID);
            if (!$result->execute()) {
                http_response_code(500);
                echo(json_encode(['error' => 'Query can not be executed']));
                die;
            }

            http_response_code(200);
            echo(json_encode(['response' => 'Success']));
        } else {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter value: attempt should be `request` or `process`']));
            die;
        }
    }

    public function activate(): void {
        $activationToken = $_POST['activationToken'] ?? $_GET['activationToken'] ?? null;
        if (!$activationToken) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameter: activationToken']));
            die;
        }
        $tokenHash = User::calculateActivationTokenHash($activationToken);
        $user = $this->creator->newInstanceByActivationToken($tokenHash);
        if (!$user) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid activation token']));
            die;
        }

        if ($user->isActivated()) {
            http_response_code(200);
            echo(json_encode(['response' => 'Already activated']));
        } else {
            $success = $user->activate();
            if (!$success) {
                http_response_code(500);
                echo(json_encode(['error' => 'Query can not be executed']));
                die;
            } else {
                http_response_code(200);
                echo(json_encode(['response' => 'Success']));
            }
        }
    }

    public function authenticate(): void {
        $email = $_POST['email'] ?? $_GET['email'] ?? null;
        $password = $_POST['password'] ?? $_GET['password'] ?? null;

        if (!$email || !$password) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameters: email or password']));
            die;
        }
        if (!empty(User::validateEmail($email, false)) || !empty(User::validatePassword($password))) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameters: email or password']));
            die;
        }

        $user = $this->creator->newInstanceByEmail($email);
        if ($user) {
            $passedPasswordHash = User::generatePasswordHash($password, $user->getSalt());
            $success = $user->getPasswordHash() == $passedPasswordHash;
        } else {
            $success = false;
        }

        http_response_code(200);
        if (!$success) {
            echo(json_encode(['response' => new StdClass]));
        } else {
            $user = $user->toArray();
            $this->handleUserData($user, true);
            echo(json_encode(['response' => $user]));
        }
    }

}
