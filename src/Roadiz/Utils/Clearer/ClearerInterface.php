<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Clearer;

interface ClearerInterface
{
    /**
     * @return boolean
     */
    public function clear();
    /**
     * @return string
     */
    public function getOutput();

    /**
     * Get global cache directory.
     *
     * @return string
     */
    public function getCacheDir();
}
