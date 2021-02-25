<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Symfony\Component\EventDispatcher\Debug\WrappedListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DispatcherCollector extends DataCollector implements Renderable
{
    private EventDispatcherInterface $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function collect()
    {
        $array = [
            'listeners' => [],
        ];
        foreach ($this->dispatcher->getListeners() as $eventName => $listeners) {
            /** @var EventSubscriberInterface $listener */
            foreach ($listeners as $priority => $listener) {
                if ($listener instanceof WrappedListener) {
                    $listener = $listener->getWrappedListener();
                }

                if (is_object($listener)) {
                    $className = get_class($listener);
                    $method = null;
                } else {
                    if (is_object($listener[0])) {
                        $className = get_class($listener[0]);
                    } else {
                        $className = '';
                    }

                    $method = $listener[1];
                }
                $array['listeners'][$eventName . '('.$priority.')'] = $className . ':' . $method;
            }
        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'dispatcher';
    }

    /**
     * @inheritDoc
     */
    public function getWidgets()
    {
        $widgets = [
            'dispatcher' => [
                'icon' => 'flag',
                'widget' => 'PhpDebugBar.Widgets.KVListWidget',
                'map' => 'dispatcher.listeners',
                'default' => '{}'
            ]
        ];

        return $widgets;
    }
}
