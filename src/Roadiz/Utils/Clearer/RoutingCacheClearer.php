<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class RoutingCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            if ($fs->exists($this->getCacheDir() . '/routing')) {
                $finder->in($this->getCacheDir() . '/routing');
                $fs->remove($finder);
                $this->output .= 'Compiled route collections have been purged.';

                return true;
            }
        }

        return false;
    }
}
