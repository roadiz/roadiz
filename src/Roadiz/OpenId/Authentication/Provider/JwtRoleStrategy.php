<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Provider;

use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;

interface JwtRoleStrategy
{
    public function supports(JwtAccountToken $token): bool;
    public function getRoles(JwtAccountToken $token): ?array;
}
