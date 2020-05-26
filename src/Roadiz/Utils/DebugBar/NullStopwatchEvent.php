<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar;

use Symfony\Component\Stopwatch\StopwatchEvent;

class NullStopwatchEvent extends StopwatchEvent
{
    public function start()
    {
        return $this;
    }

    public function stop()
    {
        return $this;
    }

    protected function getNow()
    {
        return 0.0;
    }

    public function getPeriods()
    {
        return [];
    }

    public function getDuration()
    {
        return 0.0;
    }
}
