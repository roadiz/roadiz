<?php
declare(strict_types=1);

namespace RZ\Roadiz\Workflow\Event;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class NodeStatusGuardListener implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.node.guard' => ['guard'],
            'workflow.node.guard.publish' => ['guardPublish'],
            'workflow.node.guard.archive' => ['guardArchive'],
            'workflow.node.guard.delete' => ['guardDelete'],
        ];
    }

    public function guard(GuardEvent $event)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to edit this node.',
                '1'
            ));
        }
    }

    public function guardPublish(GuardEvent $event)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES_STATUS')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to publish this node.',
                '1'
            ));
        }
    }

    public function guardArchive(GuardEvent $event)
    {
        /** @var Node $node */
        $node = $event->getSubject();
        if ($node->isLocked()) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'A locked node cannot be archived.',
                '1'
            ));
        }
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES_STATUS')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to archive this node.',
                '1'
            ));
        }
    }

    public function guardDelete(GuardEvent $event)
    {
        /** @var Node $node */
        $node = $event->getSubject();
        if ($node->isLocked()) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'A locked node cannot be deleted.',
                '1'
            ));
        }
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES_DELETE')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to delete this node.',
                '1'
            ));
        }
    }
}
