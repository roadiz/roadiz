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
 * @file NodeWorkflow.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Workflow;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class NodeWorkflow extends Workflow
{
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $definitionBuilder = new DefinitionBuilder();
        $definition = $definitionBuilder
            ->setInitialPlace(Node::DRAFT)
            ->addPlaces([
                Node::DRAFT,
                Node::PENDING,
                Node::PUBLISHED,
                Node::ARCHIVED,
                Node::DELETED
            ])
            ->addTransition(new Transition('review', Node::DRAFT, Node::PENDING))
            ->addTransition(new Transition('reject', Node::PENDING, Node::DRAFT))
            ->addTransition(new Transition('reject', Node::PUBLISHED, Node::DRAFT))
            ->addTransition(new Transition('publish', Node::DRAFT, Node::PUBLISHED))
            ->addTransition(new Transition('publish', Node::PENDING, Node::PUBLISHED))
            ->addTransition(new Transition('publish', Node::PUBLISHED, Node::PUBLISHED))
            ->addTransition(new Transition('archive', Node::PUBLISHED, Node::ARCHIVED))
            ->addTransition(new Transition('unarchive', Node::ARCHIVED, Node::DRAFT))
            ->addTransition(new Transition('delete', Node::DRAFT, Node::DELETED))
            ->addTransition(new Transition('delete', Node::PENDING, Node::DELETED))
            ->addTransition(new Transition('delete', Node::PUBLISHED, Node::DELETED))
            ->addTransition(new Transition('delete', Node::ARCHIVED, Node::DELETED))
            ->addTransition(new Transition('undelete', Node::DELETED, Node::DRAFT))
            ->build()
        ;
        $markingStore = new SingleStateMarkingStore('status');

        parent::__construct($definition, $markingStore, $dispatcher, 'node');
    }
}
