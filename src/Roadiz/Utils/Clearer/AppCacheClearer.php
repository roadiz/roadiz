<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class AppCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            if ($fs->exists($this->getCacheDir() . '/http_cache')) {
                $finder->in($this->getCacheDir() . '/http_cache');
                $fs->remove($finder);
                $this->output .= 'Application HTTP cache has been purged.';
                return true;
            }
        }

        $this->output .= 'No application HTTP cache found.';

        return false;
    }
}
