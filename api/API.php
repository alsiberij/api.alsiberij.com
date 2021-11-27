<?php


abstract class API {

    protected ?User $authorizedUser;
    protected PDO $db;
    protected EntityCreator $creator;

    public function __construct() {
        $this->db = DB::getConnection();
        if (isset($_SESSION['userID'])) {
            $userID = $_SESSION['userID'];
            if (!($this->authorizedUser = (new UserCreator())->newInstance($userID))) {
                http_response_code(403);
                echo(json_encode(['error'=>'Invalid session ID']));
                die;
            }
        } elseif($accessToken = $_POST['accessToken'] ?? $_GET['accessToken'] ?? null) {
            if (!($this->authorizedUser = (new UserCreator())->newInstanceByAccessToken($accessToken))) {
                http_response_code(403);
                echo(json_encode(['error'=>'Invalid access token']));
                die;
            }
        } else {
            $this->authorizedUser = null;
        }
    }

    public abstract function respond(string $methodName): void;

}