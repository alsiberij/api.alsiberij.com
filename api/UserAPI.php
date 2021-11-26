<?php


class UserAPI extends API implements Retrievable {

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

                if ($accessAllData || $user['privacy']) {
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
        die;
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

            if ($accessAllData || $user['privacy']) {
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
        die;
    }

}