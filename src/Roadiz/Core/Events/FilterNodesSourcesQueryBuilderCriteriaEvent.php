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
 * @file FilterNodesSourcesQueryBuilderCriteriaEvent.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\NodesSources;

class FilterNodesSourcesQueryBuilderCriteriaEvent extends FilterQueryBuilderCriteriaEvent
{
    /**
     * @inheritDoc
     */
    public function __construct(QueryBuilder $queryBuilder, $property, $value, $actualEntityName)
    {
        parent::__construct($queryBuilder, NodesSources::class, $property, $value, $actualEntityName);
    }

    /**
     * @inheritDoc
     */
    public function supports()
    {
        if ($this->actualEntityName === NodesSources::class) {
            return true;
        }

        $reflectionClass = new \ReflectionClass($this->actualEntityName);
        if ($reflectionClass->isSubclassOf(NodesSources::class)) {
            return true;
        }

        return false;
    }
}
