<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ConfigurationCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            $finder->in($this->getCacheDir())
                ->files()
                ->name('configuration.php')
                ->name('configuration.php.meta');

            $fs->remove($finder);

            $this->output .= 'Compiled configuration files have been deleted.';
        }

        return true;
    }
}
