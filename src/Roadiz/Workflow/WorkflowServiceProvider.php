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
 * @file WorkflowServiceProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Workflow;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Workflow\Event\NodeStatusGuardListener;
use Symfony\Bridge\Twig\Extension\WorkflowExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;

class WorkflowServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container['workflow.node_workflow'] = function (Container $c) {
            return new NodeWorkflow($c['dispatcher']);
        };

        $container['workflow.registry'] = function (Container $c) {
            $registry = new Registry();
            $registry->addWorkflow($c['workflow.node_workflow'], new InstanceOfSupportStrategy(Node::class));
            return $registry;
        };

        $container->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            if (true !== $c['kernel']->isInstallMode()) {
                $dispatcher->addSubscriber(new NodeStatusGuardListener($c['securityAuthorizationChecker']));
            }
            return $dispatcher;
        });

        $container->extend('twig.extensions', function ($extensions, $c) {
            $extensions->add(new WorkflowExtension($c['workflow.registry']));
            return $extensions;
        });
    }
}
