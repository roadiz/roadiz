<?php
declare(strict_types=1);

namespace RZ\Roadiz\Workflow;

use Doctrine\Common\Collections\ArrayCollection;
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

        $container->extend('twig.extensions', function (ArrayCollection $extensions, $c) {
            $extensions->add(new WorkflowExtension($c['workflow.registry']));
            return $extensions;
        });
    }
}
