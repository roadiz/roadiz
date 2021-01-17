<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Node selector and uploader form field type.
 */
class NodesType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(function ($mixedEntities) {
            if ($mixedEntities instanceof Collection) {
                return $mixedEntities->toArray();
            }
            if (!is_array($mixedEntities)) {
                return [$mixedEntities];
            }
            return $mixedEntities;
        }, function ($mixedIds) use ($options) {
            /** @var NodeRepository $repository */
            $repository = $this->entityManager
                ->getRepository(Node::class)
                ->setDisplayingAllNodesStatuses(true);
            if (is_array($mixedIds) && count($mixedIds) === 0) {
                return [];
            } elseif (is_array($mixedIds) && count($mixedIds) > 0) {
                if ($options['multiple'] === false) {
                    return $repository->findOneBy(['id' => $mixedIds]);
                }
                return $repository->findBy(['id' => $mixedIds]);
            } elseif ($options['multiple'] === true) {
                return [];
            } else {
                return $repository->findOneById($mixedIds);
            }
        }));
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'multiple' => true,
            'nodes' => [],
        ]);

        $resolver->setAllowedTypes('multiple', ['boolean']);
    }

    /**
     * {@inheritdoc}
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        /*
         * Inject data as plain nodes entities
         */
        if (!empty($options['nodes'])) {
            $view->vars['data'] = $options['nodes'];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'nodes';
    }
}
