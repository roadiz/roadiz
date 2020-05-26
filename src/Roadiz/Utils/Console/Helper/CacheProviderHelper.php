<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Console\Helper\Helper;

/**
 * CacheProviderHelper.
 */
class CacheProviderHelper extends Helper
{
    private $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @return CacheProvider
     */
    public function getCacheProvider()
    {
        return $this->cacheProvider;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ns-cache';
    }
}
