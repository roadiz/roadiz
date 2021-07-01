<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Controller;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use RZ\Roadiz\Webhook\Entity\Webhook;
use RZ\Roadiz\Webhook\Form\WebhookType;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Controllers\AbstractAdminController;

final class AdminWebhookController extends AbstractAdminController
{
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
