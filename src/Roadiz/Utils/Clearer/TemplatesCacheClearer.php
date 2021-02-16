<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class TemplatesCacheClearer extends Clearer
{
    public function clear(): bool
    {
        $fs = new Filesystem();
        $finder = new Finder();

        if ($fs->exists($this->getCacheDir())) {
            if ($fs->exists($this->getCacheDir() . '/twig_cache')) {
                $finder->in($this->getCacheDir() . '/twig_cache');
                $fs->remove($finder);

                $this->output .= 'Compiled Twig templates have been purged.';

                return true;
            }
        }

        return false;
    }
}
