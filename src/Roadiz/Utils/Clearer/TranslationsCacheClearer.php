<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class TranslationsCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            if ($fs->exists($this->getCacheDir() . '/translations')) {
                $finder->in($this->getCacheDir() . '/translations');
                $fs->remove($finder);

                $this->output .= 'Compiled translation catalogues have been purged.';

                return true;
            }
        }

        return false;
    }
}
