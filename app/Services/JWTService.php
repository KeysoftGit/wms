<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;

class JWTService
{
    protected string $secret;
    protected string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', Config::get('app.key'));
    }

    /**
     * Create a new JWT token.
     *
     * @param array $payload
     * @param int $expiry seconds from now
     * @return string
     */
    public function createToken(array $payload, int $expiry = 2592000): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Decode and verify a JWT token.
     *
     * @param string $token
     * @return object|null
     */
    public function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (\Exception $e) {
            return null;
        }
    }
}
