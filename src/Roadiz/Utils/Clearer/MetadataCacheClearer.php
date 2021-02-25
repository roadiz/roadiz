<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MetadataCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            if ($fs->exists($this->getCacheDir() . '/metadata')) {
                $finder->in($this->getCacheDir() . '/metadata');
                $fs->remove($finder);

                $this->output .= 'Serialization metadata cache have been purged.';

                return true;
            }
        }

        return false;
    }
}
