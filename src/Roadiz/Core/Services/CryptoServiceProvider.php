<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 */

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
            return $c['kernel']->getProjectDir() .
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
