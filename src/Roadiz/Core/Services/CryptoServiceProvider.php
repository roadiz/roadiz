<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Crypto\Encoder\AsymmetricUniqueKeyEncoder;
use RZ\Crypto\Encoder\SymmetricUniqueKeyEncoder;
use RZ\Crypto\Encoder\UniqueKeyEncoderInterface;
use RZ\Crypto\KeyChain\AsymmetricFilesystemKeyChain;
use RZ\Crypto\KeyChain\KeyChainInterface;

class CryptoServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register(Container $container)
    {
        $container['crypto.absolute_private_key_path'] = function (Container $c) {
            return $c['kernel']->getRootDir() .
                DIRECTORY_SEPARATOR .
                $c['config']['security']['private_key_path']
            ;
        };

        $container[UniqueKeyEncoderInterface::class] = function (Container $c) {
            /** @var EncryptionKey|null $key */
            $key = $c['crypto.private_key'];
            if (null === $key) {
                return $key;
            }
            if ($key instanceof EncryptionSecretKey) {
                $publicKey = $key->derivePublicKey();
                return new AsymmetricUniqueKeyEncoder(
                    $publicKey,
                    $key
                );
            } else {
                return new SymmetricUniqueKeyEncoder(
                    $key
                );
            }
        };

        $container[KeyChainInterface::class] = function (Container $c) {
            $absKeyFolder = dirname($c['crypto.absolute_private_key_path']);
            return new AsymmetricFilesystemKeyChain($absKeyFolder);
        };

        $container['crypto.private_key'] = function (Container $c) {
            if (file_exists($c['crypto.absolute_private_key_path'])) {
                $filename = pathinfo($c['crypto.absolute_private_key_path'], PATHINFO_FILENAME);
                return $c[KeyChainInterface::class]->get($filename);
            }
            return null;
        };
    }
}
