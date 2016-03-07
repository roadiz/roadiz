<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file JsonConfigurationServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use RZ\Roadiz\Console\Tools\Configuration;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use Pimple\Container;

/**
 * Register configuration services for dependency injection container.
 */
class JsonConfigurationServiceProvider extends AbstractConfigurationServiceProvider
{
    /**
     * @param Container $container [description]
     * @return Container
     */
    public function register(Container $container)
    {
        parent::register($container);
        /*
         * Inject app config
         */
        $container['config'] = function () {
            $configuration = new Configuration();

            if (false !== $configuration->load()) {
                return $configuration->getConfiguration();
            } else {
                throw new NoConfigurationFoundException();
            }
        };

        return $container;
    }
}
