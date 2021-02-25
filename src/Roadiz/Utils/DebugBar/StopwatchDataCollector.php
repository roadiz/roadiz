<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar;

use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBarException;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Profiler\Profile;

final class StopwatchDataCollector extends TimeDataCollector
{
    private Stopwatch $stopwatch;
    private ?Profile $twigProfile;

    /**
     * @param Stopwatch $stopwatch
     * @param Profile|null $twigProfile
     */
    public function __construct(Stopwatch $stopwatch, Profile $twigProfile = null)
    {
        parent::__construct();
        $this->stopwatch = $stopwatch;
        $this->twigProfile = $twigProfile;
    }

    /**
     * @return array
     * @throws DebugBarException
     */
    public function collect()
    {
        foreach ($this->stopwatch->getSections() as $section) {
            foreach ($section->getEvents() as $name => $event) {
                $event->ensureStopped();
                foreach ($event->getPeriods() as $period) {
                    $this->addMeasure(
                        $name,
                        $this->getRequestStartTime() + ($period->getStartTime() / 1000.0),
                        $this->getRequestStartTime() + ($period->getEndTime() / 1000.0),
                        [],
                        null
                    );
                }
            }
        }

        if (null !== $this->twigProfile) {
            $this->doAddTwigMeasure($this->twigProfile->serialize());
        }

        return parent::collect();
    }

    /**
     * @param string $profile Serialized profile
     */
    protected function doAddTwigMeasure($profile)
    {
        list($template, $name, $type, $starts, $ends, $profiles) = unserialize($profile);

        if ($type !== 'ROOT' && $type !== 'block' && isset($starts['wt']) && isset($ends['wt'])) {
            $this->addMeasure(
                $template . ' <' . $type . '>',
                $starts['wt'],
                $ends['wt'],
                [],
                null
            );
        }

        foreach ($profiles as $subProfile) {
            $this->doAddTwigMeasure($subProfile->serialize());
        }
    }
}
