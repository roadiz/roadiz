<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Controller;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Exception\TooManyWebhookTriggeredException;
use RZ\Roadiz\Webhook\Form\WebhookType;
use RZ\Roadiz\Webhook\WebhookDispatcher;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Controllers\AbstractAdminController;

final class AdminWebhookController extends AbstractAdminController
{
    public function triggerAction(Request $request, string $id)
    {
        $this->denyAccessUnlessGranted($this->getRequiredRole());

        /** @var Webhook|null $item */
        $item = $this->get('em')->find($this->getEntityClass(), $id);

        if (null === $item || !($item instanceof PersistableInterface)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessItemGranted($item);

        $form = $this->createForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var WebhookDispatcher $webhookDispatcher */
                $webhookDispatcher = $this->get(WebhookDispatcher::class);
                $webhookDispatcher->dispatch($item);
                $this->get('em')->flush();

                $msg = $this->getTranslator()->trans(
                    'webhook.%item%.will_be_triggered_in.%seconds%',
                    [
                        '%item%' => $this->getEntityName($item),
                        '%seconds%' => $item->getThrottleSeconds(),
                    ]
                );
                $this->publishConfirmMessage($request, $msg);

                return $this->redirect($this->get('urlGenerator')->generate($this->getDefaultRouteName()));
            } catch (TooManyWebhookTriggeredException $e) {
                $form->addError(new FormError('webhook.too_many_triggered_in_period'));
            }
        }

        $this->assignation['form'] = $form->createView();
        $this->assignation['item'] = $item;

        return $this->render(
            $this->getTemplateFolder() . '/trigger.html.twig',
            $this->assignation,
            null,
            $this->getTemplateNamespace()
        );
    }

    protected function supports(PersistableInterface $item): bool
    {
        return $item instanceof Webhook;
    }

    protected function getNamespace(): string
    {
        return 'webhook';
    }

    protected function createEmptyItem(Request $request): PersistableInterface
    {
        return new Webhook();
    }

    protected function getTemplateFolder(): string
    {
        return 'admin/webhooks';
    }

    protected function getRequiredRole(): string
    {
        return 'ROLE_ACCESS_WEBHOOKS';
    }

    protected function getEntityClass(): string
    {
        return Webhook::class;
    }

    protected function getFormType(): string
    {
        return WebhookType::class;
    }

    protected function getDefaultRouteName(): string
    {
        return 'webhooksHomePage';
    }

    protected function getEditRouteName(): string
    {
        return 'webhooksEditPage';
    }

    protected function getEntityName(PersistableInterface $item): string
    {
        if ($item instanceof Webhook) {
            return (string) $item;
        }
        return '';
    }
}
