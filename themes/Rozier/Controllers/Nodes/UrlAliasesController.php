<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceSeoType;
use RZ\Roadiz\CMS\Forms\UrlAliasType;
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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
            /** @var FormFactory $formFactory */
            $formFactory = $this->get('formFactory');
            $alias = new UrlAlias();
            $addAliasForm = $formFactory->createNamed(
                'add_urlalias_'.$node->getId(),
                UrlAliasType::class,
                $alias,
                [
                    'with_translation' => true
                ]
            );
            $addAliasForm->handleRequest($request);
            if ($addAliasForm->isSubmitted() && $addAliasForm->isValid()) {
                try {
                    $alias = $this->addNodeUrlAlias($alias, $node, $addAliasForm->get('translation')->getData());
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.created.%translation%', [
                        '%alias%' => $alias->getAlias(),
                        '%translation%' => $alias->getNodeSource()->getTranslation()->getName(),
                    ]);
                    $this->publishConfirmMessage($request, $msg, $source);
                    /*
                     * Dispatch event
                     */
                    $this->get('dispatcher')->dispatch(new UrlAliasCreatedEvent($alias));

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
     * @param UrlAlias $alias
     * @param Node $node
     * @param Translation $translation
     * @return UrlAlias
     */
    private function addNodeUrlAlias(UrlAlias $alias, Node $node, Translation $translation): UrlAlias
    {
        /** @var NodesSources $nodeSource */
        $nodeSource = $this->get('em')
                           ->getRepository(NodesSources::class)
                           ->setDisplayingAllNodesStatuses(true)
                           ->setDisplayingNotPublishedNodes(true)
                           ->findOneBy(['node' => $node, 'translation' => $translation]);

        if ($translation !== null && $nodeSource !== null) {
            $alias->setNodeSource($nodeSource);
            $this->get('em')->persist($alias);
            $this->get('em')->flush();

            return $alias;
        } else {
            $msg = $this->getTranslator()->trans('url_alias.no_translation.%translation%', [
                '%translation%' => $translation->getName()
            ]);
            throw new NoTranslationAvailableException($msg);
        }
    }

    /**
     * @param UrlAlias $alias
     * @param Request  $request
     *
     * @return RedirectResponse|null
     */
    private function handleSingleUrlAlias(UrlAlias $alias, Request $request): ?RedirectResponse
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->get('formFactory');
        $editForm = $formFactory->createNamed(
            'edit_urlalias_'.$alias->getId(),
            UrlAliasType::class,
            $alias
        );
        $deleteForm = $formFactory->createNamed('delete_urlalias_'.$alias->getId());
        // Match edit
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                try {
                    $this->get('em')->flush();
                    $msg = $this->getTranslator()->trans(
                        'url_alias.%alias%.updated',
                        ['%alias%' => $alias->getAlias()]
                    );
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
                } catch (\RuntimeException $exception) {
                    $editForm->addError(new FormError($exception->getMessage()));
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

    /**
     * @param NodesSources $source
     * @param Request $request
     * @return RedirectResponse|null
     */
    private function handleAddRedirection(NodesSources $source, Request $request): ?RedirectResponse
    {
        $redirection = new Redirection();
        $redirection->setRedirectNodeSource($source);
        $redirection->setType(Response::HTTP_MOVED_PERMANENTLY);

        /** @var FormFactory $formFactory */
        $formFactory = $this->get('formFactory');
        $addForm = $formFactory->createNamed(
            'add_redirection',
            RedirectionType::class,
            $redirection,
            [
                'placeholder' => $this->generateUrl($source),
                'only_query' => true
            ]
        );

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

    /**
     * @param Redirection $redirection
     * @param Request $request
     * @return RedirectResponse|null
     */
    private function handleSingleRedirection(Redirection $redirection, Request $request): ?RedirectResponse
    {
        /** @var FormFactory $formFactory */
        $formFactory = $this->get('formFactory');
        $editForm = $formFactory->createNamed(
            'edit_redirection_'.$redirection->getId(),
            RedirectionType::class,
            $redirection,
            [
                'only_query' => true
            ]
        );
        $deleteForm = $formFactory->createNamed('delete_redirection_'.$redirection->getId());

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
