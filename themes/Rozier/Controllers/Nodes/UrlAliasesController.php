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

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;
use RZ\Roadiz\Core\Events\FilterNodesSourcesEvent;
use RZ\Roadiz\Core\Events\NodesSourcesEvents;
use RZ\Roadiz\Core\Events\FilterUrlAliasEvent;
use RZ\Roadiz\Core\Events\UrlAliasEvents;

/**
 * {@inheritdoc}
 */
class UrlAliasesController extends RozierApp
{
    /**
     * Return aliases form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAliasesAction(Request $request, $nodeId, $translationId = null)
    {
        $this->validateAccessForRole('ROLE_ACCESS_NODES');

        if (null === $translationId && $translationId < 1) {
            $translation = $this->getService('defaultTranslation');
        } else {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $translationId);
        }

        $source = $this->getService('em')
                       ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                       ->findOneBy(['translation' => $translation, 'node.id' => (int) $nodeId]);

        $node = $source->getNode();

        if ($source !== null &&
            $node !== null) {
            $uas = $this->getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')
                        ->findAllFromNode($node->getId());

            $this->assignation['node'] = $node;
            $this->assignation['source'] = $source;
            $this->assignation['aliases'] = [];
            $this->assignation['translation'] = $translation;
            $this->assignation['available_translations'] = $node->getHandler()->getAvailableTranslations();

            /*
             * SEO Form
             */
            $seoForm = $this->buildEditSEOForm($source);
            $this->assignation['seoForm'] = $seoForm->createView();
            $seoForm->handleRequest($request);

            if ($seoForm->isValid()) {
                if ($this->editSEO($seoForm->getData(), $source)) {
                    $msg = $this->getTranslator()->trans('node.seo.updated');
                    $this->publishConfirmMessage($request, $msg, $source);

                    /*
                     * Dispatch event
                     */
                    $event = new FilterNodesSourcesEvent($source);
                    $this->getService('dispatcher')->dispatch(NodesSourcesEvents::NODE_SOURCE_UPDATED, $event);
                } else {
                    $msg = $this->getTranslator()->trans('node.seo.not.updated');
                    $this->publishErrorMessage($request, $msg, $source);
                }

                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'nodesEditSEOPage',
                    ['nodeId' => $node->getId(), 'translationId' => $translationId]
                ));
            }

            /*
             * each url alias edit form
             */
            foreach ($uas as $alias) {
                $editForm = $this->buildEditUrlAliasForm($alias);
                $deleteForm = $this->buildDeleteUrlAliasForm($alias);

                // Match edit
                $editForm->handleRequest($request);
                if ($editForm->isValid() &&
                    $editForm->getData()['urlaliasId'] == $alias->getId()) {
                    if ($this->editUrlAlias($editForm->getData(), $alias)) {
                        $msg = $this->getTranslator()->trans('url_alias.%alias%.updated', ['%alias%' => $alias->getAlias()]);
                        $this->publishConfirmMessage($request, $msg, $source);

                        /*
                         * Dispatch event
                         */
                        $event = new FilterUrlAliasEvent($alias);
                        $this->getService('dispatcher')->dispatch(UrlAliasEvents::URL_ALIAS_UPDATED, $event);
                    } else {
                        $msg = $this->getTranslator()->trans('url_alias.%alias%.no_update.already_exists', ['%alias%' => $alias->getAlias()]);
                        $this->publishErrorMessage($request, $msg, $source);
                    }

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    return $this->redirect($this->generateUrl(
                        'nodesEditSEOPage',
                        ['nodeId' => $node->getId(), 'translationId' => $translationId]
                    ));
                }

                // Match delete
                $deleteForm->handleRequest($request);

                if ($deleteForm->isValid() &&
                    $deleteForm->getData()['urlaliasId'] == $alias->getId()) {
                    $this->deleteUrlAlias($editForm->getData(), $alias);
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.deleted', ['%alias%' => $alias->getAlias()]);
                    $this->publishConfirmMessage($request, $msg, $source);

                    /*
                     * Dispatch event
                     */
                    $event = new FilterUrlAliasEvent($alias);
                    $this->getService('dispatcher')->dispatch(UrlAliasEvents::URL_ALIAS_DELETED, $event);
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
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

            if ($form->isValid()) {
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
                    $event = new FilterUrlAliasEvent($ua);
                    $this->getService('dispatcher')->dispatch(UrlAliasEvents::URL_ALIAS_CREATED, $event);

                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage(), $source);
                } catch (NoTranslationAvailableException $e) {
                    $this->publishErrorMessage($request, $e->getMessage(), $source);
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'nodesEditSEOPage',
                    ['nodeId' => $node->getId(), 'translationId' => $translationId]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('nodes/editAliases.html.twig', $this->assignation);
        }

        return $this->throw404();
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return RZ\Roadiz\Core\Entities\UrlAlias
     */
    private function addNodeUrlAlias($data, Node $node)
    {
        if ($data['nodeId'] == $node->getId()) {
            $translation = $this->getService('em')
                                ->find('RZ\Roadiz\Core\Entities\Translation', (int) $data['translationId']);

            $nodeSource = $this->getService('em')
                               ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
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
                    $this->getService('em')->persist($ua);
                    $this->getService('em')->flush();

                    return $ua;
                } catch (\Exception $e) {
                    $msg = $this->getTranslator()->trans('url_alias.%alias%.no_creation.already_exists', ['%alias%' => $testingAlias]);

                    throw new EntityAlreadyExistsException($msg, 1);
                }
            } else {
                $msg = $this->getTranslator()->trans('url_alias.no_translation.%translation%', ['%translation%' => $translation->getName()]);

                throw new NoTranslationAvailableException($msg, 1);
            }
        }

        return null;
    }

    /**
     * Edit NodesSources SEO fields.
     *
     * @param array                               $data
     * @param RZ\Roadiz\Core\Entities\NodesSources $nodeSource
     *
     * @return boolean
     */
    private function editSEO(array $data, $nodeSource)
    {
        if ($data['id'] == $nodeSource->getId()) {
            $nodeSource->setMetaTitle($data['metaTitle']);
            $nodeSource->setMetaKeywords($data['metaKeywords']);
            $nodeSource->setMetaDescription($data['metaDescription']);

            $this->getService('em')->flush();
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    private function urlAliasExists($name)
    {
        return (boolean) $this->getService('em')
                              ->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')
                              ->exists($name);
    }
    /**
     * @param string $name
     *
     * @return boolean
     */
    private function nodeNameExists($name)
    {
        return (boolean) $this->getService('em')
                              ->getRepository('RZ\Roadiz\Core\Entities\Node')
                              ->exists($name);
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\UrlAlias $ua
     *
     * @return boolean
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
                $this->getService('em')->flush();

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param array                           $data
     * @param RZ\Roadiz\Core\Entities\UrlAlias $ua
     */
    private function deleteUrlAlias($data, UrlAlias $ua)
    {
        if ($data['urlaliasId'] == $ua->getId()) {
            $this->getService('em')->remove($ua);
            $this->getService('em')->flush();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddUrlAliasForm(Node $node)
    {
        $defaults = [
            'nodeId' => $node->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('nodeId', 'hidden', [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('alias', 'text', [
                            'label' => 'urlAlias',
                        ])
                        ->add('translationId', new \RZ\Roadiz\CMS\Forms\TranslationsType(), [
                            'label' => 'translation',
                        ]);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\UrlAlias $ua
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditUrlAliasForm(UrlAlias $ua)
    {
        $defaults = [
            'urlaliasId' => $ua->getId(),
            'alias' => $ua->getAlias(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('urlaliasId', 'hidden', [
                            'data' => $ua->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('alias', 'text', [
                            'label' => false,
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\NodesSources $ns
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditSEOForm($ns)
    {
        $defaults = [
            'id' => $ns->getId(),
            'metaTitle' => $ns->getMetaTitle(),
            'metaKeywords' => $ns->getMetaKeywords(),
            'metaDescription' => $ns->getMetaDescription(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('id', 'hidden', [
                            'data' => $ns->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('metaTitle', 'text', [
                            'label' => 'metaTitle',
                            'required' => false,
                            'attr' => [
                                'data-max-length' => 55,
                            ],
                        ])
                        ->add('metaKeywords', 'text', [
                            'label' => 'metaKeywords',
                            'required' => false,
                        ])
                        ->add('metaDescription', 'textarea', [
                            'label' => 'metaDescription',
                            'required' => false,
                        ]);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\UrlAlias $ua
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteUrlAliasForm(UrlAlias $ua)
    {
        $defaults = [
            'urlaliasId' => $ua->getId(),
        ];
        $builder = $this->createFormBuilder($defaults)
                        ->add('urlaliasId', 'hidden', [
                            'data' => $ua->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
