<?php
declare(strict_types=1);

namespace RZ\Roadiz\Webhook\Form;

use RZ\Roadiz\CMS\Forms\NodesType;
use RZ\Roadiz\CMS\Forms\YamlType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class WebhookType extends AbstractType
{
    private array $types;

    /**
     * @param array $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('messageType', ChoiceType::class, [
            'required' => true,
            'label' => 'webhooks.messageType',
            'choices' => $this->types
        ])->add('uri', TextareaType::class, [
            'required' => true,
            'label' => 'webhooks.uri',
            'constraints' => [
                new NotNull(),
                new Url()
            ]
        ])->add('payload', YamlType::class, [
            'required' => false,
            'label' => 'webhooks.payload',
        ])->add('throttleSeconds', IntegerType::class, [
            'required' => true,
            'label' => 'webhooks.throttleSeconds',
            'constraints' => [
                new NotNull(),
                new GreaterThan(0),
            ]
        ])->add('automatic', CheckboxType::class, [
            'required' => false,
            'label' => 'webhooks.automatic',
            'help' => 'webhooks.automatic.help',
        ])->add('rootNode', NodesType::class, [
            'required' => false,
            'label' => 'webhooks.rootNode',
            'help' => 'webhooks.rootNode.help',
            'multiple' => false
        ]);

        $builder->get('payload')->addModelTransformer(new CallbackTransformer(function (?array $model) {
            return $model ? Yaml::dump($model): null;
        }, function (?string $yaml) {
            try {
                return $yaml ? Yaml::parse($yaml) : null;
            } catch (ParseException $e) {
                throw new TransformationFailedException($e->getMessage(), 0, $e);
            }
        }));
    }
}
