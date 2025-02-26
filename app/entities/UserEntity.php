<?php

namespace App\Entities;

/**
 * UserEntity represents a single user
 * 
 * @author Lukas Velek
 */
class UserEntity extends AEntity {
    private string $id;
    private string $username;
    private string $fullname;
    private ?string $email;
    private string $dateCreated;
    private bool $isTechnical;

    /**
     * Class constructor
     * 
     * @param string $id User ID
     * @param string $username Username
     * @param string $fullname Fullname
     * @param ?string $email Email
     * @param string $dateCreated Date created
     * @param bool $isTechnical Is user technical
     */
    public function __construct(string $id, string $username, string $fullname, ?string $email, string $dateCreated, bool $isTechnical) {
        $this->id = $id;
        $this->username = $username;
        $this->fullname = $fullname;
        $this->email = $email;
        $this->dateCreated = $dateCreated;
        $this->isTechnical = $isTechnical;
    }

    /**
     * Returns user ID
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * Returns username
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * Returns fullname
     */
    public function getFullname(): string {
        return $this->fullname;
    }

    /**
     * Returns email
     */
    public function getEmail(): ?string {
        return $this->email;
    }

    /**
     * Returns date created
     */
    public function getDateCreated(): string {
        return $this->dateCreated;
    }
    
    /**
     * Returns true if user is a technical user
     */
    public function isTechnical(): bool {
        return $this->isTechnical;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['userId' => 'string', 'username' => 'string', 'fullname' => 'string', 'email' => '?string', 'dateCreated' => 'string', 'isTechnical' => 'bool']);

        return new self($row->userId, $row->username, $row->fullname, $row->email, $row->dateCreated, $row->isTechnical);
    }
}

?>