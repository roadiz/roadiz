<?php
declare(strict_types=1);

namespace RZ\Roadiz\Workflow;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class NodeWorkflow extends Workflow
{
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $definitionBuilder = new DefinitionBuilder();
        $definition = $definitionBuilder
            ->setInitialPlaces(Node::DRAFT)
            ->addPlaces([
                Node::DRAFT,
                Node::PENDING,
                Node::PUBLISHED,
                Node::ARCHIVED,
                Node::DELETED
            ])
            ->addTransition(new Transition('review', Node::DRAFT, Node::PENDING))
            ->addTransition(new Transition('review', Node::PUBLISHED, Node::PENDING))
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
        $markingStore = new MethodMarkingStore(true, 'status');

        parent::__construct($definition, $markingStore, $dispatcher, 'node');
    }
}
