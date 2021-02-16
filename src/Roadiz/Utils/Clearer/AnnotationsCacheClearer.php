<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class AnnotationsCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            if ($fs->exists($this->getCacheDir() . '/annotations')) {
                $finder->in($this->getCacheDir() . '/annotations');
                $fs->remove($finder);

                $this->output .= 'Doctrine annotations cache have been purged.';

                return true;
            }
        }

        return false;
    }
}
