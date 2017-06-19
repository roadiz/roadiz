<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file StopwatchDataCollector.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\DebugBar;

use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBarException;
use Symfony\Component\Stopwatch\Stopwatch;

class StopwatchDataCollector extends TimeDataCollector
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;
    /**
     * @var \Twig_Profiler_Profile
     */
    private $twigProfile;

    /**
     * @param Stopwatch $stopwatch
     * @param \Twig_Profiler_Profile|null $twigProfile
     * @internal param float $requestStartTime
     */
    public function __construct(Stopwatch $stopwatch, \Twig_Profiler_Profile $twigProfile = null)
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

        if ($type !== 'ROOT' && $type !== 'block') {
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
