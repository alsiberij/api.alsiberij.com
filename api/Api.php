<?php


abstract class Api {

    protected ?User $authorizedUser;
    protected PDO $db;
    protected EntityCreator $creator;

    public function __construct() {
        $this->db = DB::getConnection();
        $r = null;
        if (isset($_SESSION['userID'])) {
            $userID = $_SESSION['userID'];
            $result = $this->db->prepare('SELECT * FROM '.TABLE_USER.' WHERE id = :ID');
            $result->bindParam(':ID', $userID, PDO::PARAM_INT);
            if (!$result->execute()) {
                http_response_code(500);
                echo(json_encode(['error'=>'Query cannot be executed']));
                die;
            }
            $r = $result->fetch(PDO::FETCH_ASSOC);
            if (!$r) {
                http_response_code(403);
                echo(json_encode(['error'=>'Invalid session ID']));
                die;
            }
        } elseif($accessToken = $_POST['accessToken'] ?? $_GET['accessToken'] ?? null) {
            $result = $this->db->prepare('SELECT * FROM ' . TABLE_USER . ' WHERE access_token = :token');
            $result->bindParam(':token', $accessToken);
            if (!$result->execute()) {
                http_response_code(500);
                echo(json_encode(['error'=>'Query cannot be executed']));
                die;
            }
            $r = $result->fetch(PDO::FETCH_ASSOC);
            if (!$r) {
                http_response_code(403);
                echo(json_encode(['error'=>'Invalid access token']));
                die;
            }
        }

        if ($r) {
            $this->authorizedUser = new User($r['ID'], $r['activation'], $r['accessToken'], $r['administrator'],
                $r['moderator'], $r['privacy'], $r['nickname'], $r['email'], $r['emailPrivacy'], $r['password'],
                $r['salt'], $r['registrationDate'], $r['balance'], $r['balancePrivacy'], $r['avatar'], $r['birthday'],
                $r['location'], $r['bio'], $r['likes'], $r['comments'], $r['paidOrders'], $r['lastSeenTime'],
                $r['lastSeenTimePrivacy']);
        } else {
            $this->authorizedUser = null;
        }
    }

    public abstract function respond(string $methodName): void;

}