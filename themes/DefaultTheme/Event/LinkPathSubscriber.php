<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 * @file LinkPathSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\DefaultTheme\Event;

use GeneratedNodeSources\NSLink;
use RZ\Roadiz\Core\Events\FilterNodeSourcePathEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LinkPathSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            /*
             * Needs to execute this BEFORE default nodes-sources path generation
             */
            NodesSourcesEvents::NODE_SOURCE_PATH_GENERATING => [['onNodesSourcesPath', 0]],
        ];
    }

    /**
     * @param FilterNodeSourcePathEvent $event
     */
    public function onNodesSourcesPath(FilterNodeSourcePathEvent $event): void
    {
        $source = $event->getNodeSource();
        if ($source instanceof NSLink) {
            /*
             * Prevent default nodes-sources path generation
             * to be executed.
             */
            $event->stopPropagation();

            if (isset($source->getRefNode()[0])) {
                $realNode = $source->getRefNode()[0]->getNodeSources()->first();
                $urlGenerator = new NodesSourcesUrlGenerator(
                    null,
                    $realNode,
                    $event->isForceLocale()
                );
                $event->setPath($urlGenerator->getNonContextualUrl($event->getTheme(), $event->getParameters()));
            } else {
                $event->setPath('');
            }
        }
    }
}
