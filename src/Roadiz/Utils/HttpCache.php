<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\Esi;
use Symfony\Component\HttpKernel\HttpCache\HttpCache as BaseHttpCache;
use Symfony\Component\HttpKernel\HttpCache\Store;

/**
 * Manages HTTP cache objects in a Container.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class HttpCache extends BaseHttpCache
{
    /**
     * @var null|string
     */
    protected $cacheDir;

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @param Kernel $kernel An Kernel instance
     * @param string|null $cacheDir The cache directory (default used if null)
     */
    public function __construct(Kernel $kernel, ?string $cacheDir = null)
    {
        $this->kernel = $kernel;
        $this->cacheDir = $cacheDir;

        parent::__construct(
            $kernel,
            $this->createStore(),
            $this->createSurrogate(),
            array_merge(['debug' => $kernel->isDebug()], $this->getOptions())
        );
    }

    /**
     * Forwards the Request to the backend and returns the Response.
     *
     * @param Request  $request A Request instance
     * @param bool     $catch     Whether to catch exceptions or not
     * @param Response $entry   A Response instance (the stale entry if present, null otherwise)
     *
     * @return Response A Response instance
     */
    protected function forward(Request $request, $catch = false, Response $entry = null): Response
    {
        if ($this->kernel instanceof Kernel) {
            $this->kernel->boot();
            $this->kernel->getContainer()->offsetSet('cache', $this);
            $this->kernel->getContainer()->offsetSet($this->getSurrogate()->getName(), $this->getSurrogate());
        }

        return parent::forward($request, $catch, $entry);
    }

    /**
     * Returns an array of options to customize the Cache configuration.
     *
     * @return array An array of options
     */
    protected function getOptions()
    {
        return [];
    }

    protected function createSurrogate()
    {
        return new Esi();
    }

    protected function createStore()
    {
        return new Store($this->cacheDir ?: $this->kernel->getCacheDir().'/http_cache');
    }
}
