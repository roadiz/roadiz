<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use RZ\Roadiz\Message\Handler\HttpRequestMessageHandler;
use RZ\Roadiz\Message\HttpRequestMessage;
use RZ\Roadiz\Webhook\EventSubscriber\AutomaticWebhookSubscriber;
use RZ\Roadiz\Webhook\Form\WebhooksChoiceType;
use RZ\Roadiz\Webhook\Form\WebhookType;
use RZ\Roadiz\Webhook\Message\GenericJsonPostMessage;
use RZ\Roadiz\Webhook\Message\GitlabPipelineTriggerMessage;
use RZ\Roadiz\Webhook\Message\NetlifyBuildHookMessage;
use RZ\Roadiz\Webhook\Message\WebhookMessageFactory;
use RZ\Roadiz\Webhook\Message\WebhookMessageFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Translation\Translator;

class WebhookServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        $pimple['webhook.types'] = function () {
            return [
                'webhook.type.generic_json_post' => GenericJsonPostMessage::class,
                'webhook.type.gitlab_pipeline' => GitlabPipelineTriggerMessage::class,
                'webhook.type.netlify_build_hook' => NetlifyBuildHookMessage::class,
            ];
        };

        $pimple[WebhookType::class] = function (Container $c) {
            return new WebhookType($c['webhook.types']);
        };

        $pimple[WebhooksChoiceType::class] = function (Container $c) {
            return new WebhooksChoiceType($c[ManagerRegistry::class]);
        };

        $pimple[WebhookMessageFactoryInterface::class] = function () {
            return new WebhookMessageFactory();
        };

        $pimple[WebhookDispatcher::class] = function (Container $c) {
            return new ThrottledWebhookDispatcher(
                $c[WebhookMessageFactoryInterface::class],
                $c[MessageBusInterface::class],
                new CacheStorage($c[CacheItemPoolInterface::class])
            );
        };

        $pimple->extend('messenger.handlers', function (array $handlers, Container $c) {
            $handlers[GitlabPipelineTriggerMessage::class] = [
                $c[HttpRequestMessageHandler::class]
            ];
            $handlers[NetlifyBuildHookMessage::class] = [
                $c[HttpRequestMessageHandler::class]
            ];
            $handlers[GenericJsonPostMessage::class] = [
                $c[HttpRequestMessageHandler::class]
            ];
            /*
             * Default handler for all messages implementing HttpRequestMessage
             */
            $handlers[HttpRequestMessage::class] = [
                $c[HttpRequestMessageHandler::class]
            ];

            return $handlers;
        });

        $pimple->extend('doctrine.entities_paths', function (array $paths) {
            $paths[] = dirname(__FILE__) . '/Entity';
            return $paths;
        });

        $pimple->extend('translator', function (Translator $translator) {
            $locales = ['en', 'fr'];
            foreach ($locales as $locale) {
                $translator->addResource(
                    'xlf',
                    dirname(__FILE__) . '/Resources/translations/messages.'.$locale.'.xlf',
                    $locale
                );
            }
            return $translator;
        });

        $pimple->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            $dispatcher->addSubscriber(new AutomaticWebhookSubscriber(
                $c[WebhookDispatcher::class],
                $c[ManagerRegistry::class],
                $c['factory.handler']
            ));
            return $dispatcher;
        });
    }
}
