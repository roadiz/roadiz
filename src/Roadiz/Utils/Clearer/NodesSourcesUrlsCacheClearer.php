<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Doctrine\Common\Cache\CacheProvider;

class NodesSourcesUrlsCacheClearer extends Clearer
{
    private CacheProvider $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        parent::__construct('');
        $this->cacheProvider = $cacheProvider;
    }

    public function clear(): bool
    {
        $this->output .= 'Node-sources URLs cache ' . $this->cacheProvider->getNamespace() . ': ';

        if (!$this->cacheProvider->flushAll()) {
            if (!$this->cacheProvider->deleteAll()) {
                $this->output .= 'failed';
                return false;
            } else {
                $this->output .= 'deleted';
            }
        } else {
            $this->output .= 'flushed';
        }

        return true;
    }
}
