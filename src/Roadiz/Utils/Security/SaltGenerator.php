<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Security;

class SaltGenerator extends RandomGenerator implements SaltGeneratorInterface
{
    public function generateSalt()
    {
        return strtr(base64_encode($this->getRandomNumber()), '{}', '-_');
    }

    private function getRandomNumber()
    {
        $nbBytes = 24;

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
