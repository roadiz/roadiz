<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class AssetsClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            $finder->in($this->getCacheDir());
            $fs->remove($finder);
            $this->output .= 'Assets cache has been purged.';

            return true;
        }

        return false;
    }
}
