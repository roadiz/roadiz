<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log\Handler;

use Monolog\Handler\GelfHandler;

/**
 * Fault tolerant GelfHandler.
 *
 * @package RZ\Roadiz\Utils\Log\Handler
 */
class TolerantGelfHandler extends GelfHandler
{
    /**
     * Do not throw exception if external host is not reachable.
     *
     * @param array $record
     */
    protected function write(array $record): void
    {
        if (class_exists('\Gelf\PublisherInterface')) {
            try {
                $this->publisher->publish($record['formatted']);
            } catch (\Exception $e) {
                // Do nothing
            }
        }
    }
}
