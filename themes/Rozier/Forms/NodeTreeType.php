<?php
declare(strict_types=1);

namespace Themes\Rozier\Forms;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Themes\Rozier\Widgets\TreeWidgetFactory;

/**
 * Node tree embedded type in a node source form.
 *
 * This form type is not published inside Roadiz CMS as it needs
 * NodeTreeWidget which is part of Rozier Theme.
 */
class NodeTreeType extends AbstractType
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected RequestStack $requestStack;
    protected ManagerRegistry $managerRegistry;
    protected TreeWidgetFactory $treeWidgetFactory;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RequestStack $requestStack
     * @param ManagerRegistry $managerRegistry
     * @param TreeWidgetFactory $treeWidgetFactory
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        TreeWidgetFactory $treeWidgetFactory
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack = $requestStack;
        $this->treeWidgetFactory = $treeWidgetFactory;
        $this->managerRegistry = $managerRegistry;
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

        if ($options['nodeTypeField']->getType() !== AbstractField::CHILDREN_T) {
            throw new \RuntimeException("Given field is not a NodeTypeField::CHILDREN_T field.", 1);
        }

        $view->vars['authorizationChecker'] = $this->authorizationChecker;
        /*
         * Inject data as plain document entities
         */
        $view->vars['request'] = $this->requestStack->getCurrentRequest();

        /*
         * Linked types to create quick add buttons
         */
        $defaultValues = explode(',', $options['nodeTypeField']->getDefaultValues() ?? '');
        foreach ($defaultValues as $key => $value) {
            $defaultValues[$key] = trim($value);
        }

        $nodeTypes = $this->managerRegistry->getRepository(NodeType::class)
            ->findBy(
                ['name' => $defaultValues],
                ['displayName' => 'ASC']
            );

        $view->vars['linkedTypes'] = $nodeTypes;

        $nodeTree = $this->treeWidgetFactory->createNodeTree(
            $options['nodeSource']->getNode(),
            $options['nodeSource']->getTranslation()
        );
        /*
         * If node-type has been used as default values,
         * we need to restrict node-tree display too.
         */
        if (is_array($nodeTypes) && count($nodeTypes) > 0) {
            $nodeTree->setAdditionalCriteria([
                'nodeType' => $nodeTypes
            ]);
        }

        $view->vars['nodeTree'] = $nodeTree;
        $view->vars['nodeStatuses'] = [
            Node::getStatusLabel(Node::DRAFT) => Node::DRAFT,
            Node::getStatusLabel(Node::PENDING) => Node::PENDING,
            Node::getStatusLabel(Node::PUBLISHED) => Node::PUBLISHED,
            Node::getStatusLabel(Node::ARCHIVED) => Node::ARCHIVED,
            Node::getStatusLabel(Node::DELETED) => Node::DELETED,
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'childrennodes';
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'nodeSource',
            'nodeTypeField',
        ]);

        $resolver->setAllowedTypes('nodeSource', [NodesSources::class]);
        $resolver->setAllowedTypes('nodeTypeField', [NodeTypeField::class]);
    }
}
