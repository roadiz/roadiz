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
 * @file NodeStatusListener.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

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
     * NodeStatusListener constructor.
     *
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
                1
            ));
        }
    }

    public function guardPublish(GuardEvent $event)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES_STATUS')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to publish this node.',
                1
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
                1
            ));
        }
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES_STATUS')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to archive this node.',
                1
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
                1
            ));
        }
        if (!$this->authorizationChecker->isGranted('ROLE_ACCESS_NODES_DELETE')) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'User is not allowed to delete this node.',
                1
            ));
        }
    }
}
