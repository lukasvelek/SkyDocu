<?php

namespace App\Entities;

/**
 * ExternalSystemTokenEntity represents a token entity
 * 
 * @author Lukas Velek
 */
class ExternalSystemTokenEntity {
    private string $tokenId;
    private string $hash;
    private string $validUntil;

    /**
     * Class constructor
     * 
     * @param string $tokenId Token ID
     * @param string $hash Hash
     * @param string $validUntil Valid until
     */
    public function __construct(
        string $tokenId,
        string $hash,
        string $validUntil
    ) {
        $this->tokenId = $tokenId;
        $this->hash = $hash;
        $this->validUntil = $validUntil;
    }

    /**
     * Returns hash
     */
    public function getHash(): string {
        return $this->hash;
    }

    /**
     * Returns token ID
     */
    public function getTokenId(): string {
        return $this->tokenId;
    }

    /**
     * Returns date valid until
     */
    public function getValidUntil(): string {
        return $this->validUntil;
    }

    /**
     * Generates token and returns it
     * 
     * @param bool $encodeToBase64 Encode to base64
     */
    public function generateToken(bool $encodeToBase64 = true) {
        $data = [
            'validUntil' => $this->validUntil,
            'hash' => $this->hash,
            'tokenId' => $this->tokenId
        ];

        if($encodeToBase64) {
            return base64_encode(json_encode($data));
        } else {
            return json_encode($data);
        }
    }

    /**
     * Returns a new entity instance from generated token
     * 
     * @param string $token Token
     * @param bool $isEncodedToBase64 True if token is encoded to base64
     */
    public static function getFromGeneratedToken(string $token, bool $isEncodedToBase64 = true): static {
        if($isEncodedToBase64) {
            $data = json_decode(base64_decode($token), true);
        } else {
            $data = json_decode($token, true);
        }

        return new self(
            $data['tokenId'],
            $data['hash'],
            $data['validUntil']
        );
    }
}