<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Doctrine\Common\Cache\CacheProvider;

/**
 * NodesSourcesUrlsCacheClearer.
 */
class NodesSourcesUrlsCacheClearer extends Clearer
{
    private $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        parent::__construct('');
        $this->cacheProvider = $cacheProvider;
    }

    public function clear()
    {
        $this->output .= 'Node-sources URLs cache: ' . $this->cacheProvider->getNamespace() . ' — ';

        if (!$this->cacheProvider->flushAll()) {
            if (!$this->cacheProvider->deleteAll()) {
                $this->output .= '<error>FAIL</error>';
            } else {
                $this->output .= '<info>OK</info>: DELETED';
            }
        } else {
            $this->output .= '<info>OK</info>: FLUSHED';
        }

        return true;
    }
}
