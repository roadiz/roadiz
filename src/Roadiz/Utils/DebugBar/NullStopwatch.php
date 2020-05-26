<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar;

use Symfony\Component\Stopwatch\Stopwatch;

class NullStopwatch extends Stopwatch
{
    public function start($name, $category = null)
    {
        return new NullStopwatchEvent(0, 'null');
    }

    public function isStarted($name)
    {
        return true;
    }

    public function stop($name)
    {
        return new NullStopwatchEvent(0, 'null');
    }
}
