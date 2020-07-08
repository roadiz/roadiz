<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use RZ\Roadiz\OpenId\Discovery;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

abstract class DiscoveryAwareValidator implements JwtValidator
{
    /**
     * @var Discovery|null
     */
    protected $discovery;

    /**
     * IssuerValidator constructor.
     *
     * @param Discovery|null $discovery
     */
    public function __construct(?Discovery $discovery)
    {
        $this->discovery = $discovery;
    }
}
