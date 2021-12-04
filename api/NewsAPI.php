<?php


class NewsAPI extends API implements Retrievable, Assessable, Creatable {

    public function __construct() {
        parent::__construct();
        $this->creator = new NewsCreator();
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
            case 'vote': {
                $this->vote();;
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
        $rawNewsIDs = $_POST['newsIDs'] ?? $_GET['newsIDs'] ?? '';
        $newsIDs = explode(',', $rawNewsIDs);
        $error = false;
        foreach ($newsIDs as $newsID) {
            if (!is_numeric($newsID)) {
                $error = true;
                break;
            }
        }
        if (empty($newsIDs) || $error) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: newsID']));
            die;
        }

        $newsList = [];
        foreach ($newsIDs as $newsID) {
            $news = $this->creator->newInstance($newsID);
            if ($news) {
                $hasAccess = false;
                if ($news->getPrivacy() || $this->authorizedUser &&
                    ($this->authorizedUser->getID() == $news->getAuthorID() || $this->authorizedUser->isAdministrator())) {
                    $hasAccess = true;
                }
                if ($hasAccess) {
                    $newsList[] = $news->toArray();
                }
            }
        }
        http_response_code(200);
        echo(json_encode(['response'=>$newsList]));
    }

    public function getAll(): void {
        $newsObjList = $this->creator->allInstances();
        $newsList = [];
        foreach ($newsObjList as $news) {
            $add = true;
            if (!$news->getPrivacy()) {
                if (!($this->authorizedUser && ($this->authorizedUser->isAdministrator() || $this->authorizedUser->getID() == $news->getAuthorID()))) {
                    $add = false;
                }
            }
            if ($add) {
                $newsList[] = $news->toArray();
            }
        }
        http_response_code(200);
        echo(json_encode(['response'=>$newsList]));
    }

    public function vote(): void {
        if (!$this->authorizedUser) {
            http_response_code(403);
            echo(json_encode(['error' => 'Unauthorized']));
            die;
        }

        $resourceID = $_POST['resourceID'] ?? $_GET['resourceID'] ?? '';
        $voteType = $_POST['voteType'] ?? $_GET['voteType'] ?? '';
        $voteType = strtolower($voteType);
        if (!$resourceID || !is_numeric($resourceID)) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameter: resourceID']));
            die;
        }
        $resource = $this->creator->newInstance($resourceID);
        if (!$resource) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: resourceID']));
            die;
        }
        if (!$resource->getPrivacy() && !$this->authorizedUser->isAdministrator() && !($this->authorizedUser->getID() == $resource->getAuthorID())) {
            http_response_code(403);
            echo(json_encode(['error' => 'Access denied']));
            die;
        }
        if (!$voteType) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameter: voteType']));
            die;
        } elseif (!in_array($voteType, ['up', 'down'])) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: voteType']));
            die;
        }
        $voteType = $voteType == 'up';

        $success = (new NewsVoteCreator())->create($resource, $this->authorizedUser, $voteType);
        if (!$success) {
            http_response_code(500);
            echo(json_encode(['error' => 'Something went wrong...']));
            die;
        }
        http_response_code(200);
        echo(json_encode(['response' => $resource->getRating()]));
    }

    public function create(): void {
        if (!($this->authorizedUser && ($this->authorizedUser->isAdministrator() || $this->authorizedUser->isModerator()))) {
            http_response_code(403);
            echo(json_encode(['error' => 'Access denied']));
            die;
        }

        $privacy = $_POST['privacy'] ?? $_GET['privacy'] ?? '1';
        if ($privacy != '1' && $privacy != '0') {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: privacy']));
            die;
        }
        $privacy = $privacy == '1';
        $importance = $_POST['importance'] ?? $_GET['importance'] ?? '0';
        if ($importance != '1' && $importance != '0') {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: importance']));
            die;
        }
        $importance = $importance == '1';
        $title = $_POST['title'] ?? $_GET['title'] ?? '';
        $content = $_POST['content'] ?? $_GET['content'] ?? '';

        $error = $this->creator->create($this->authorizedUser, $privacy, $importance, $title, $content);
        if ($error) {
            http_response_code(400);
            echo(json_encode(['error' => $error]));
            die;
        }
        http_response_code(200);
        echo(json_encode(['response' => 'Success']));
    }

    public function delete(): void {
        $newsID = $_POST['newsID'] ?? $_GET['newsID'] ?? '';
        if (!$newsID) {
            http_response_code(400);
            echo(json_encode(['error' => 'Missing parameter: newsID']));
            die;
        }
        if (!is_numeric($newsID)) {
            http_response_code(400);
            echo(json_encode(['error' => 'Invalid parameter: newsID']));
            die;
        }
        $resource = $this->creator->newInstance($newsID);
        if (!$resource) {
            http_response_code(404);
            echo(json_encode(['error' => 'Resource not found']));
            die;
        }

        if (!($this->authorizedUser && ($this->authorizedUser->isAdministrator() || ($this->authorizedUser->isModerator() && $this->authorizedUser->getID() == $resource->getAuthorID())))) {
            http_response_code(403);
            echo(json_encode(['error' => 'Access denied']));
            die;
        }

        $this->creator->delete($resource);
        http_response_code(200);
        echo(json_encode(['response' => 'Success']));
    }
}
