<?php
declare(strict_types=1);

namespace RZ\Roadiz\JWT;

use Lcobucci\JWT\Configuration;

interface JwtConfigurationFactory
{
    public function create(): Configuration;
}
