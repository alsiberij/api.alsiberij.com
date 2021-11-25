<?php


class User extends Entity {

    protected bool $isActivated;
    protected ?string $accessToken;
    protected bool $isAdministrator;
    protected bool $isModerator;
    protected bool $privacy;
    protected string $nickname;
    protected string $email;
    protected bool $emailPrivacy;
    protected string $password;
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

    public function __construct(int $ID, bool $isActivated, ?string $accessToken, bool $isAdmin, bool $contentCreator,
                                bool $privacy, string $nickname, string $email, bool $emailPrivacy, string $password,
                                string $salt, string $registrationDate, int $balance, bool $balancePrivacy, bool $avatar,
                                ?string $birthday, ?string $location, ?string $bio, int $likes, int $comments,
                                int $paidOrders, string $lastSeenTime, bool $lastSeenTimePrivacy) {
        parent::__construct($ID);
        $this->isActivated = $isActivated;
        $this->accessToken = $accessToken;
        $this->isAdministrator = $isAdmin;
        $this->isModerator = $contentCreator;
        $this->privacy = $privacy;
        $this->nickname = $nickname;
        $this->email = $email;
        $this->emailPrivacy = $emailPrivacy;
        $this->password = $password;
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

}