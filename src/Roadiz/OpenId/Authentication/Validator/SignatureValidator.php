<?php
declare(strict_types=1);

namespace RZ\Roadiz\OpenId\Authentication\Validator;

use Jose\Component\Core\Util\RSAKey;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use RZ\Roadiz\OpenId\Authentication\JwtAccountToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class SignatureValidator extends DiscoveryAwareValidator
{
    /**
     * @inheritDoc
     */
    public function __invoke(JwtAccountToken $token): void
    {
        $jwt = (new Parser())->parse((string) $token->getCredentials()); // Parses from a string

        /*
         * Verify JWT signature if asymmetric crypto is used and if PHP gmp extension is loaded.
         */
        if ($this->discovery->canVerifySignature() && null !== $jwkSet = $this->discovery->getJWKSet()) {
            if (in_array(
                (string) $jwt->getHeader('alg'),
                $this->discovery->get('id_token_signing_alg_values_supported', [])
            )) {
                if ((string) $jwt->getHeader('alg') === 'RS256') {
                    // Select a RS256 signature key from jwk set provided by discovery.
                    $signer = new \Lcobucci\JWT\Signer\Rsa\Sha256();
                    $verifiedSig = false;
                    foreach ($jwkSet->all() as $jwk) {
                        $publicKey = new Key(RSAKey::createFromJWK($jwk)->toPEM());
                        if (true === $jwt->verify($signer, $publicKey)) {
                            $verifiedSig = true;
                        }
                    }
                    if (false === $verifiedSig) {
                        throw new BadCredentialsException('Bad JWT signature, none of jwks key could sign token.');
                    }
                } elseif ((string) $jwt->getHeader('alg') === 'HS256') {
                    throw new BadCredentialsException('HS256 JWT signature is not supported by Roadiz yet.');
                } else {
                    throw new BadCredentialsException(
                        (string) $jwt->getHeader('alg') . ' JWT signature is not supported by Roadiz yet.'
                    );
                }
            }
        }
    }
}
