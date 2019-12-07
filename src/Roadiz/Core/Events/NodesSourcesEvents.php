<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file NodesSourcesEvents.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Events;

/**
 * @deprecated
 */
final class NodesSourcesEvents
{
    /**
     * Event nodeSource.created is triggered each time a node-source
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_CREATED = 'nodeSource.created';

    /**
     * Event nodeSource.updated is triggered each time a node-source
     * is updated.
     * This event is dispatched BEFORE entity manager FLUSHED.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_PRE_UPDATE = 'nodeSource.pre_update';

    /**
     * Event nodeSource.updated is triggered each time a node-source
     * is updated.
     * This event is dispatched AFTER entity manager FLUSHED.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_UPDATED = 'nodeSource.updated';

    /**
     * Event nodeSource.deleted is triggered each time a node-source
     * is deleted.
     *
     * This event is dispatched BEFORE entity manager FLUSHED.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterNodesSourcesEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_DELETED = 'nodeSource.deleted';

    /**
     * Event nodeSource.indexing is triggered each time a node-source
     * is being indexed in a Solr server.
     *
     * Event listener will be given a:
     * \RZ\Roadiz\Core\Events\FilterSolariumNodeSourceEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_INDEXING = 'nodeSource.indexing';

    /**
     * Event triggered when a node-source path is being generating by the NodeRouter.
     * This event allows generating different paths according your node types.
     *
     * Event listener will be given a:
     * \RZ\Roadiz\Core\Events\FilterNodeSourcePathEvent instance
     *
     * @var string
     * @deprecated
     */
    const NODE_SOURCE_PATH_GENERATING = 'nodeSource.pathGenerating';
}
