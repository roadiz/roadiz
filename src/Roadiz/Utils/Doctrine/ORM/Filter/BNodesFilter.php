<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file BNodesFilter.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Doctrine\ORM\Filter;

use RZ\Roadiz\Core\Events\QueryBuilderEvents;

/**
 * @package RZ\Roadiz\Utils\Doctrine\ORM\Filter
 */
class BNodesFilter extends ANodesFilter
{
    public static function getSubscribedEvents()
    {
        return [
            QueryBuilderEvents::QUERY_BUILDER_BUILD_FILTER => [
                // NodesSources should be first as properties are
                // more detailed and precise.
                ['onNodesSourcesQueryBuilderBuild', 40],
                ['onNodeQueryBuilderBuild', 30],
            ]
        ];
    }

    /**
     * @return string
     */
    protected function getProperty()
    {
        return 'bNodes';
    }

    /**
     * @return string
     */
    protected function getNodeJoinAlias()
    {
        return 'b_n';
    }

    /**
     * @return string
     */
    protected function getNodeFieldJoinAlias()
    {
        return 'b_n_f';
    }
}
