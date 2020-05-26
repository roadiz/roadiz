<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

/**
 * Clearer.
 */
class Clearer implements ClearerInterface
{
    protected $output;
    protected $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function clear()
    {
        return false;
    }

    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Get global cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }
}
