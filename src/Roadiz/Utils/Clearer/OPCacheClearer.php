<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

class OPCacheClearer implements ClearerInterface
{
    protected $output;

    public function clear()
    {
        if (function_exists('opcache_reset') &&
            true === opcache_reset()) {
            $this->output = 'PHP OPCache has been reset.';
        } else {
            $this->output = 'PHP OPCache is disabled.';
        }

        return false;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getCacheDir()
    {
        return '';
    }
}
