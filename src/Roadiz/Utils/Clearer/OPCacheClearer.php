<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

class OPCacheClearer implements ClearerInterface
{
    protected string $output;

    public function clear(): bool
    {
        if (function_exists('opcache_reset') &&
            true === opcache_reset()) {
            $this->output = 'PHP OPCache has been reset.';
            return true;
        } else {
            $this->output = 'PHP OPCache is disabled.';
        }

        return false;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getCacheDir(): string
    {
        return '';
    }
}
