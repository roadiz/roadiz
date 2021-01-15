<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceSeoType;
use RZ\Roadiz\CMS\Forms\TranslationsType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesUpdatedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasCreatedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasDeletedEvent;
use RZ\Roadiz\Core\Events\UrlAlias\UrlAliasUpdatedEvent;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\RedirectionType;
use Themes\Rozier\RozierApp;

class UrlAliasesController extends RozierApp
{
    /**
     * Return aliases form for requested node.
     *
     * @param Request $request
     * @param int     $nodeId
     * @param int|null  $translationId
     *
     * @return Response
     */
    public function editAliasesAction(Request $request, int $nodeId, ?int $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        if (null === $translationId && $translationId < 1) {
            $translation = $this->get('defaultTranslation');
        } else {
            $translation = $this->get('em')->find(Translation::class, $translationId);
        }
        /** @var NodesSources|null $source */
        $source = $this->get('em')
                       ->getRepository(NodesSources::class)
                       ->setDisplayingAllNodesStatuses(true)
                       ->setDisplayingNotPublishedNodes(true)
                       ->findOneBy(['translation' => $translation, 'node.id' => $nodeId]);

        if ($source !== null && null !== $node = $source->getNode()) {
            $redirections = $this->get('em')
                ->getRepository(Redirection::class)
                ->findBy([
                    'redirectNodeSource' => $node->getNodeSources()->toArray()
                ]);
            $uas = $this->get('em')
                        ->getRepository(UrlAlias::class)
                        ->findAllFromNode($node->getId());
            $availableTranslations = $this->get('em')
                ->getRepository(Translation::class)
                ->findAvailableTranslationsForNode($node);

            $this->assignation['node'] = $node;
            $this->assignation['source'] = $source;
            $this->assignation['aliases'] = [];
            $this->assignation['redirections'] = [];
            $this->assignation['translation'] = $translation;
            $this->assignation['available_translations'] = $availableTranslations;

            /*
             * SEO Form
             */
            $seoForm = $this->createForm(NodeSourceSeoType::class, $source);
            $seoForm->handleRequest($request);
            if ($seoForm->isSubmitted() && $seoForm->isValid()) {
                $this->get('em')->flush();
                $msg = $this->getTranslator()->trans('node.seo.updated');
                $this->publishConfirmMessage($request, $msg, $source);
                /*
                 * Dispatch event
                 */
                $this->get('dispatcher')->dispatch(new NodesSourcesUpdatedEvent($source));
                return $this->redirect($this->generateUrl(
                    'nodesEditSEOPage',
                    ['nodeId' => $node->getId(), 'translationId' => $translationId]
                ));
            }

            if (null !== $response = $this->handleAddRedirection($source, $request)) {
                return $response;
            }
            /*
             * each url alias edit form
             */
            /** @var UrlAlias $alias */
            foreach ($uas as $alias) {
                if (null !== $response = $this->handleSingleUrlAlias($alias, $request)) {
                    return $response;
                }
            }

            /** @var Redirection $redirection */
            foreach ($redirections as $redirection) {
                if (null !== $response = $this->handleSingleRedirection($redirection, $request)) {
                    return $response;
                }
            }

            /*
             * Main ADD url alias form
             */
            $addAliasForm = $this->buildAddUrlAliasForm($node);
            $addAliasForm->handleRequest($request);
            if ($addAliasForm->isSubmitted() && $addAliasForm->isValid()) {
                try {
                    $ua = $this->addNodeUrlAlias($addAliasForm->getData(), $node);
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.created.%translation%', [
                        '%alias%' => $ua->getAlias(),
                        '%translation%' => $ua->getNodeSource()->getTranslation()->getName(),
                    ]);
                    $this->publishConfirmMessage($request, $msg, $source);
                    /*
                     * Dispatch event
                     */
                    $this->get('dispatcher')->dispatch(new UrlAliasCreatedEvent($ua));

                    return $this->redirect($this->generateUrl(
                        'nodesEditSEOPage',
                        ['nodeId' => $node->getId(), 'translationId' => $translationId]
                    ).'#manage-aliases');
                } catch (EntityAlreadyExistsException $e) {
                    $addAliasForm->addError(new FormError($e->getMessage()));
                } catch (NoTranslationAvailableException $e) {
                    $addAliasForm->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['form'] = $addAliasForm->createView();
            $this->assignation['seoForm'] = $seoForm->createView();

            return $this->render('nodes/editAliases.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array $data
     * @param Node  $node
     *
     * @return UrlAlias|null
     * @throws EntityAlreadyExistsException
     * @throws NoTranslationAvailableException
     */
    private function addNodeUrlAlias(array $data, Node $node)
    {
        if ($data['nodeId'] == $node->getId()) {
            /** @var Translation $translation */
            $translation = $this->get('em')->find(Translation::class, (int) $data['translationId']);

            /** @var NodesSources $nodeSource */
            $nodeSource = $this->get('em')
                               ->getRepository(NodesSources::class)
                               ->setDisplayingAllNodesStatuses(true)
                               ->setDisplayingNotPublishedNodes(true)
                               ->findOneBy(['node' => $node, 'translation' => $translation]);

            if ($translation !== null &&
                $nodeSource !== null) {
                $testingAlias = StringHandler::slugify($data['alias']);
                if ($this->nodeNameExists($testingAlias) ||
                    $this->urlAliasExists($testingAlias)) {
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.no_creation.already_exists', ['%alias%' => $data['alias']]);
                    throw new EntityAlreadyExistsException($msg, 1);
                }

                try {
                    $ua = new UrlAlias($nodeSource);
                    $ua->setAlias($data['alias']);
                    $this->get('em')->persist($ua);
                    $this->get('em')->flush();

                    return $ua;
                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.no_creation.already_exists', ['%alias%' => $testingAlias]);

                    throw new EntityAlreadyExistsException($msg);
                }
            } else {
                $msg = $this->getTranslator()->trans('url_alias.no_translation.%translation%', [
                    '%translation%' => $translation->getName()
                ]);

                throw new NoTranslationAvailableException($msg);
            }
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    private function urlAliasExists(string $name)
    {
        return (boolean) $this->get('em')
                              ->getRepository(UrlAlias::class)
                              ->exists($name);
    }
    /**
     * @param string $name
     *
     * @return boolean
     */
    private function nodeNameExists(string $name)
    {
        return (boolean) $this->get('em')
                              ->getRepository(Node::class)
                              ->setDisplayingNotPublishedNodes(true)
                              ->exists($name);
    }

    /**
     * @param array    $data
     * @param UrlAlias $ua
     *
     * @return bool
     * @throws EntityAlreadyExistsException
     */
    private function editUrlAlias(array $data, UrlAlias $ua)
    {
        $testingAlias = StringHandler::slugify($data['alias']);
        if ($testingAlias != $ua->getAlias() &&
            ($this->nodeNameExists($testingAlias) ||
                $this->urlAliasExists($testingAlias))) {
            $msg = $this->getTranslator()->trans(
                'url_alias.%alias%.no_update.already_exists',
                ['%alias%' => $data['alias']]
            );

            throw new EntityAlreadyExistsException($msg, 1);
        }

        if ($data['urlaliasId'] == $ua->getId()) {
            try {
                $ua->setAlias($data['alias']);
                $this->get('em')->flush();

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param Node $node
     *
     * @return FormInterface
     */
    private function buildAddUrlAliasForm(Node $node)
    {
        $defaults = [
            'nodeId' => $node->getId(),
        ];

        $builder = $this->createNamedFormBuilder('add_url_alias', $defaults)
                        ->add('nodeId', HiddenType::class, [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('alias', TextType::class, [
                            'label' => false,
                            'attr' => [
                                'placeholder' => 'urlAlias',
                            ],
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ]
                        ])
                        ->add('translationId', TranslationsType::class, [
                            'label' => false,
                            'entityManager' => $this->get('em'),
                        ]);

        return $builder->getForm();
    }

    /**
     * @param UrlAlias $ua
     *
     * @return FormInterface
     */
    private function buildEditUrlAliasForm(UrlAlias $ua)
    {
        $defaults = [
            'urlaliasId' => $ua->getId(),
            'alias' => $ua->getAlias(),
        ];

        $builder = $this->createNamedFormBuilder('edit_urlalias_'.$ua->getId(), $defaults)
                        ->add('urlaliasId', HiddenType::class, [
                            'data' => $ua->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('alias', TextType::class, [
                            'label' => false,
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param UrlAlias $alias
     * @param Request  $request
     *
     * @return RedirectResponse|null
     */
    private function handleSingleUrlAlias(UrlAlias $alias, Request $request): ?RedirectResponse
    {
        $editForm = $this->buildEditUrlAliasForm($alias);
        $deleteForm = $this->createNamedFormBuilder('delete_urlalias_'.$alias->getId())->getForm();
        // Match edit
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                if ($this->editUrlAlias($editForm->getData(), $alias)) {
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.updated', ['%alias%' => $alias->getAlias()]);
                    $this->publishConfirmMessage($request, $msg, $alias->getNodeSource());
                    /*
                     * Dispatch event
                     */
                    $this->get('dispatcher')->dispatch(new UrlAliasUpdatedEvent($alias));

                    return $this->redirect($this->generateUrl(
                        'nodesEditSEOPage',
                        [
                            'nodeId' => $alias->getNodeSource()->getNode()->getId(),
                            'translationId' => $alias->getNodeSource()->getTranslation()->getId()
                        ]
                    ).'#manage-aliases');
                } else {
                    $msg = $this->getTranslator()->trans(
                        'url_alias.%alias%.no_update.already_exists',
                        ['%alias%' => $alias->getAlias()]
                    );
                    $editForm->addError(new FormError($msg));
                }
            } catch (EntityAlreadyExistsException $e) {
                $editForm->addError(new FormError($e->getMessage()));
            }
        }

        // Match delete
        $deleteForm->handleRequest($request);
        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $this->get('em')->remove($alias);
            $this->get('em')->flush();
            $msg = $this->getTranslator()->trans('url_alias.%alias%.deleted', ['%alias%' => $alias->getAlias()]);
            $this->publishConfirmMessage($request, $msg, $alias->getNodeSource());

            /*
             * Dispatch event
             */
            $this->get('dispatcher')->dispatch(new UrlAliasDeletedEvent($alias));

            return $this->redirect($this->generateUrl(
                'nodesEditSEOPage',
                [
                    'nodeId' => $alias->getNodeSource()->getNode()->getId(),
                    'translationId' => $alias->getNodeSource()->getTranslation()->getId()
                ]
            ).'#manage-aliases');
        }

        $this->assignation['aliases'][] = [
            'alias' => $alias,
            'editForm' => $editForm->createView(),
            'deleteForm' => $deleteForm->createView(),
        ];

        return null;
    }

    private function handleAddRedirection(NodesSources $source, Request $request): ?RedirectResponse
    {
        $redirection = new Redirection();
        $redirection->setRedirectNodeSource($source);
        $redirection->setType(Response::HTTP_MOVED_PERMANENTLY);

        /** @var FormFactory $formFactory */
        $formFactory = $this->get('formFactory');
        $addForm = $formFactory->createNamedBuilder(
            'add_redirection',
            RedirectionType::class,
            $redirection,
            [
                'placeholder' => $this->generateUrl($source),
                'only_query' => true
            ]
        )->getForm();

        $addForm->handleRequest($request);
        if ($addForm->isSubmitted() && $addForm->isValid()) {
            $this->get('em')->persist($redirection);
            $this->get('em')->flush();
            return $this->redirect($this->generateUrl(
                'nodesEditSEOPage',
                [
                    'nodeId' => $redirection->getRedirectNodeSource()->getNode()->getId(),
                    'translationId' => $redirection->getRedirectNodeSource()->getTranslation()->getId()
                ]
            ).'#manage-redirections');
        }

        $this->assignation['addRedirection'] = $addForm->createView();

        return null;
    }

    private function handleSingleRedirection(Redirection $redirection, Request $request): ?RedirectResponse
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->get('formFactory');
        /** @var FormInterface $editForm */
        $editForm = $formFactory->createNamedBuilder(
            'edit_redirection_'.$redirection->getId(),
            RedirectionType::class,
            $redirection,
            [
                'only_query' => true
            ]
        )->getForm();
        $deleteForm = $this->createNamedFormBuilder('delete_redirection_'.$redirection->getId())->getForm();

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->get('em')->flush();
            return $this->redirect($this->generateUrl(
                'nodesEditSEOPage',
                [
                    'nodeId' => $redirection->getRedirectNodeSource()->getNode()->getId(),
                    'translationId' => $redirection->getRedirectNodeSource()->getTranslation()->getId()
                ]
            ).'#manage-redirections');
        }

        // Match delete
        $deleteForm->handleRequest($request);
        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $this->get('em')->remove($redirection);
            $this->get('em')->flush();
            return $this->redirect($this->generateUrl(
                'nodesEditSEOPage',
                [
                    'nodeId' => $redirection->getRedirectNodeSource()->getNode()->getId(),
                    'translationId' => $redirection->getRedirectNodeSource()->getTranslation()->getId()
                ]
            ).'#manage-redirections');
        }
        $this->assignation['redirections'][] = [
            'redirection' => $redirection,
            'editForm' => $editForm->createView(),
            'deleteForm' => $deleteForm->createView(),
        ];

        return null;
    }
}
