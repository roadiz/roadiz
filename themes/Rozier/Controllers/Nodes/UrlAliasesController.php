<?php
/*
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 *
 * @file UrlAliasesController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceSeoType;
use RZ\Roadiz\CMS\Forms\TranslationsType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class UrlAliasesController extends RozierApp
{
    /**
     * Return aliases form for requested node.
     *
     * @param Request $request
     * @param int     $nodeId
     * @param int     $translationId
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function editAliasesAction(Request $request, $nodeId, $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        if (null === $translationId && $translationId < 1) {
            $translation = $this->get('defaultTranslation');
        } else {
            $translation = $this->get('em')
                                ->find(Translation::class, (int) $translationId);
        }
        /** @var NodesSources|null $source */
        $source = $this->get('em')
                       ->getRepository(NodesSources::class)
                       ->setDisplayingAllNodesStatuses(true)
                       ->setDisplayingNotPublishedNodes(true)
                       ->findOneBy(['translation' => $translation, 'node.id' => (int) $nodeId]);

        if ($source !== null && null !== $node = $source->getNode()) {
            $uas = $this->get('em')
                        ->getRepository(UrlAlias::class)
                        ->findAllFromNode($node->getId());
            $availableTranslations = $this->get('em')
                ->getRepository(Translation::class)
                ->findAvailableTranslationsForNode($node);

            $this->assignation['node'] = $node;
            $this->assignation['source'] = $source;
            $this->assignation['aliases'] = [];
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

            /*
             * each url alias edit form
             */
            /** @var UrlAlias $alias */
            foreach ($uas as $alias) {
                $editForm = $this->buildEditUrlAliasForm($alias);
                $deleteForm = $this->createNamedFormBuilder('delete_urlalias_'.$alias->getId())->getForm();
                // Match edit
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    try {
                        if ($this->editUrlAlias($editForm->getData(), $alias)) {
                            $msg = $this->getTranslator()->trans('url_alias.%alias%.updated', ['%alias%' => $alias->getAlias()]);
                            $this->publishConfirmMessage($request, $msg, $source);
                            /*
                             * Dispatch event
                             */
                            $this->get('dispatcher')->dispatch(new UrlAliasUpdatedEvent($alias));

                            return $this->redirect($this->generateUrl(
                                'nodesEditSEOPage',
                                ['nodeId' => $node->getId(), 'translationId' => $translationId]
                            ));
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
                    $this->publishConfirmMessage($request, $msg, $source);

                    /*
                     * Dispatch event
                     */
                    $this->get('dispatcher')->dispatch(new UrlAliasDeletedEvent($alias));

                    return $this->redirect($this->generateUrl(
                        'nodesEditSEOPage',
                        ['nodeId' => $node->getId(), 'translationId' => $translationId]
                    ));
                }

                $this->assignation['aliases'][] = [
                    'alias' => $alias,
                    'editForm' => $editForm->createView(),
                    'deleteForm' => $deleteForm->createView(),
                ];
            }

            /*
             * =======================
             * Main ADD url alias form
             */
            $form = $this->buildAddUrlAliasForm($node);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $ua = $this->addNodeUrlAlias($form->getData(), $node);
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
                    ));
                } catch (EntityAlreadyExistsException $e) {
                    $form->addError(new FormError($e->getMessage()));
                } catch (NoTranslationAvailableException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }

            $this->assignation['form'] = $form->createView();
            $this->assignation['seoForm'] = $seoForm->createView();

            return $this->render('nodes/editAliases.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param array $data
     * @param Node  $node
     *
     * @return UrlAlias
     * @throws EntityAlreadyExistsException
     * @throws NoTranslationAvailableException
     */
    private function addNodeUrlAlias($data, Node $node)
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
    private function urlAliasExists($name)
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
    private function nodeNameExists($name)
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
    private function editUrlAlias($data, UrlAlias $ua)
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
                                new NotBlank(),
                            ],
                        ])
                        ->add('alias', TextType::class, [
                            'label' => false,
                            'attr' => [
                                'placeholder' => 'urlAlias',
                            ],
                            'constraints' => [
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
                                new NotBlank(),
                            ],
                        ])
                        ->add('alias', TextType::class, [
                            'label' => false,
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
