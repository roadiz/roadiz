<?php
declare(strict_types=1);

namespace RZ\Roadiz\Tests;

use Doctrine\ORM\Tools\ToolsException;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class KernelDependentCase for test which need a valid Kernel.
 *
 * @package RZ\Roadiz\Tests
 */
abstract class KernelDependentCase extends TestCase implements ContainerAwareInterface
{
    /**
     * @var Kernel
     */
    public static $kernel;

    /**
     * @return Request
     */
    public static function getMockRequest()
    {
        return Request::createFromGlobals();
    }

    /**
     * @throws ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        static::$kernel = new Kernel('test', true, false);
        static::$kernel->boot();

        $request = static::getMockRequest();
        static::$kernel->getContainer()->offsetSet('request', $request);
        static::$kernel->get('requestStack')->push($request);
    }

    public static function tearDownAfterClass(): void
    {
        static::$kernel->shutdown();
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return static::$kernel->getContainer();
    }

    /**
     * @param Container $container
     * @return ContainerAwareInterface
     */
    public function setContainer(Container $container)
    {
        return static::$kernel->setContainer($container);
    }

    /**
     * Return a service from container.
     *
     * @param string $serviceName
     * @return mixed
     */
    public function get($serviceName)
    {
        return static::$kernel->get($serviceName);
    }

    /**
     * Returns true if the service is defined.
     *
     * @param string $serviceName
     * @return bool true if the service is defined, false otherwise
     */
    public function has($serviceName)
    {
        return static::$kernel->has($serviceName);
    }
}
