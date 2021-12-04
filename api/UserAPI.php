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
            case 'refreshToken': {
                $this->refreshToken();
                return;
            }
            case 'revokeToken': {
                $this->revokeToken();
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
        unset($user['accessToken']);
        unset($user['accessTokenExpiration']);
        unset($user['activationStatus']);
        unset($user['activationTokenHash']);
        unset($user['passwordHash']);
        unset($user['salt']);
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
        $rawUserIDs = $_POST['userIDs'] ?? $_GET['userIDs'] ?? '';
        $userIDs = explode(',', $rawUserIDs);
        $error = false;
        foreach ($userIDs as $userID) {
            if (!is_numeric($userID)) {
                $error = true;
                break;
            }
        }
        if (empty($userIDs) || $error) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: userIDs']));
            die;
        }

        $usersList = [];
        foreach ($userIDs as $userID) {
            $user = $this->creator->newInstance($userID);
            if ($user) {
                $accessAllData = false;
                if ($this->authorizedUser &&
                    ($this->authorizedUser->getID() == $user->getID() || $this->authorizedUser->isAdministrator())) {
                    $accessAllData = true;
                }
                if ($accessAllData || ($user->isActivated() && $user->getPrivacy())) {
                    $user = $user->toArray();
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

        $error = $this->creator->create($nickname, $email, $password);

        if ($error) {
            http_response_code(400);
            echo(json_encode(['error' => $error]));
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
                    <body>
                        <table>
                            <tr>
                                <td>Премногоуважаемый(ая) <b>' . $this->authorizedUser->getNickname(). '</b> <br>Ваш токен удаления аккаунта ' . $this->authorizedUser->getDeletionToken() . ' .</td>
                            </tr> 
                        </table>
                    </body>
            ';
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
            if (!$this->creator->delete($this->authorizedUser, $deleteToken)) {
                http_response_code(400);
                echo(json_encode(['error' => 'Invalid deleteToken']));
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
        $needToken = $_POST['needToken'] ?? $_GET['needToken'] ?? null;

        if (!$email || !$password) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameters: email or password']));
            die;
        }

        $email = strtolower($email);

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
            if ($needToken && $needToken == 'true') {
                $tokenArray = $user->getAccessToken();
                if ($tokenArray) {
                    echo(json_encode(['response' => $tokenArray]));
                } else {
                    http_response_code(500);
                    echo(json_encode(['error' => 'Query can not be executed']));
                    die;
                }
            } else {
                echo(json_encode(['response' => ['ID' => $user->getID()]]));
            }
        }
    }

    public function refreshToken(): void {
        if (!$this->authorizedUser) {
            http_response_code(403);
            echo(json_encode(['error' => 'Unauthorized']));
            die;
        }
        $accessToken = $_POST['accessToken'] ?? $_GET['accessToken'] ?? null;
        if (!$accessToken) {
            http_response_code(400);
            echo(json_encode(['error' => 'Token can\'t be refreshed if you are authorized by session ID']));
            die;
        }

        $newToken = $this->authorizedUser->refreshAccessToken();
        if ($newToken) {
            http_response_code(200);
            echo(json_encode(['response' => $newToken]));
        } else {
            http_response_code(500);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }
    }

    public function revokeToken(): void {
        if (!$this->authorizedUser) {
            http_response_code(403);
            echo(json_encode(['error' => 'Unauthorized']));
            die;
        }
        $accessToken = $_POST['accessToken'] ?? $_GET['accessToken'] ?? null;
        if (!$accessToken) {
            http_response_code(400);
            echo(json_encode(['error' => 'Token can\'t be revoked if you are authorized by session ID']));
            die;
        }

        $success = $this->authorizedUser->revokeAccessToken();
        if ($success) {
            http_response_code(200);
            echo(json_encode(['response' => 'Success']));
        } else {
            http_response_code(500);
            echo(json_encode(['error' => 'Query can not be executed']));
            die;
        }
    }

}
