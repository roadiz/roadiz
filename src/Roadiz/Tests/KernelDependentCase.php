<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file KernelDependentCase.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Tests;

use Pimple\Container;
use RZ\Roadiz\Core\Bags\SettingsBag;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Core\Kernel;

/**
 * Class KernelDependentCase for test which need a valid Kernel.
 *
 * @package RZ\Roadiz\Tests
 */
abstract class KernelDependentCase extends \PHPUnit_Framework_TestCase implements ContainerAwareInterface
{
    /**
     * @return Request
     */
    public static function getMockRequest()
    {
        return Request::createFromGlobals();
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public static function setUpBeforeClass()
    {
        $kernel = Kernel::getInstance('test', true, false);
        $kernel->boot();

        $request = static::getMockRequest();
        $kernel->getContainer()->offsetSet('request', $request);
        $kernel->get('requestStack')->push($request);
    }

    public static function tearDownAfterClass()
    {
        SettingsBag::clear();
        Kernel::getInstance()->shutdown();
        Kernel::destroy();
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        Kernel::getInstance()->getContainer();
    }

    /**
     * @param Container $container
     * @return ContainerAwareInterface
     */
    public function setContainer(Container $container)
    {
        Kernel::getInstance()->setContainer($container);
    }

    /**
     * Return a service from container.
     *
     * @param string $serviceName
     * @return mixed
     */
    public function get($serviceName)
    {
        Kernel::getInstance()->getContainer()->offsetGet($serviceName);
    }

    /**
     * Returns true if the service is defined.
     *
     * @param string $serviceName
     * @return bool true if the service is defined, false otherwise
     */
    public function has($serviceName)
    {
        Kernel::getInstance()->getContainer()->offsetExists($serviceName);
    }
}
