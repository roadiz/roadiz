<?php
declare(strict_types=1);

namespace RZ\Roadiz\JWT\Validation\Constraint;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

class UserInfoEndpoint implements Constraint
{
    /**
     * @var string
     */
    protected $userInfoEndpoint;

    /**
     * @param string $userInfoEndpoint
     */
    public function __construct(string $userInfoEndpoint)
    {
        $this->userInfoEndpoint = $userInfoEndpoint;
    }

    public function assert(Token $token): void
    {
        try {
            $client = new Client();
            $client->get($this->userInfoEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token->toString(),
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ConstraintViolation(
                'Userinfo cannot be fetch from Identity provider',
                $e->getCode(),
                $e
            );
        }
    }
}
