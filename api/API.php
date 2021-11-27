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
            $tokenHash = md5($accessToken) . md5(md5($accessToken));
            if (!($this->authorizedUser = (new UserCreator())->newInstanceByAccessToken($tokenHash))) {
                http_response_code(403);
                echo(json_encode(['error'=>'Invalid access token']));
                die;
            }
        }
    }

    public abstract function respond(string $methodName): void;

}