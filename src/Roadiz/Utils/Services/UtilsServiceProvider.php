<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
 *
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file UtilsServiceProvider.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Utils\Node\NodeNameChecker;
use RZ\Roadiz\Utils\Node\UniqueNodeGenerator;
use RZ\Roadiz\Utils\Node\UniversalDataDuplicator;

class UtilsServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        /**
         * @param Container $container
         *
         * @return NodeNameChecker
         */
        $container['utils.nodeNameChecker'] = function (Container $container) {
            return new NodeNameChecker($container['em']);
        };
        /**
         * @param Container $container
         *
         * @return UniqueNodeGenerator
         */
        $container['utils.uniqueNodeGenerator'] = function (Container $container) {
            return new UniqueNodeGenerator($container['em']);
        };
        /**
         * @param Container $container
         *
         * @return UniversalDataDuplicator
         */
        $container['utils.universalDataDuplicator'] = function (Container $container) {
            return new UniversalDataDuplicator($container['em']);
        };

        return $container;
    }
}
