<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

class TokenGenerator extends RandomGenerator implements TokenGeneratorInterface
{
    public function generateToken()
    {
        return rtrim(strtr(base64_encode($this->getRandomNumber()), '+/', '-_'), '=');
    }

    /**
     * @return string
     */
    private function getRandomNumber(): string
    {
        $nbBytes = 32;

        // try OpenSSL
        if ($this->useOpenSsl) {
            $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);

            if (false !== $bytes && true === $strong) {
                return $bytes;
            }

            if (null !== $this->logger) {
                $this->logger->info('OpenSSL did not produce a secure random number.');
            }
        }

        return hash('sha256', uniqid(mt_rand(), true), true);
    }
}
