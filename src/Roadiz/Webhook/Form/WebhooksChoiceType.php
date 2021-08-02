<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Form;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Webhook\Entity\Webhook;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhooksChoiceType extends ChoiceType
{
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ChoiceListFactoryInterface|null $choiceListFactory
     * @param TranslatorInterface|null $translator
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ChoiceListFactoryInterface $choiceListFactory = null,
        ?TranslatorInterface $translator = null
    ) {
        parent::__construct($choiceListFactory, $translator);
        $this->managerRegistry = $managerRegistry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer(new CallbackTransformer(function (?Webhook $webhook) {
            if (null === $webhook) {
                return null;
            }
            return $webhook->getId();
        }, function (?string $id) {
            if (null === $id) {
                return null;
            }
            return $this->managerRegistry->getRepository(Webhook::class)->findOneBy(['id' => $id]);
        }));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        /** @var Webhook[] $webhooks */
        $webhooks = $this->managerRegistry->getRepository(Webhook::class)->findAll();
        $choices = [];
        foreach ($webhooks as $webhook) {
            $choices[(string) $webhook] = $webhook->getId();
        }
        $resolver->setDefault('choices', $choices);
    }
}
