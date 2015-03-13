<?php
/*
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodesTagsController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\CMS\Forms\SeparatorType;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesTrait;

/**
 * Nodes tags controller
 *
 * {@inheritdoc}
 */
class NodesTagsController extends RozierApp
{
    use NodesTrait;

    /**
     * Return tags form for requested node.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $nodeId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editTagsAction(Request $request, $nodeId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId);

        $translation = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                            ->findDefault();

        if (null !== $translation) {
            $source = $this->getService('em')
                           ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
                           ->findOneBy([
                               'translation' => $translation,
                               'node.id' => (int) $nodeId,
                           ]);

            if (null !== $source &&
                null !== $translation) {
                $node = $source->getNode();

                $this->assignation['translation'] = $translation;
                $this->assignation['node'] = $node;
                $this->assignation['source'] = $source;

                $form = $this->buildEditTagsForm($node);

                $form->handleRequest();

                if ($form->isValid()) {
                    $this->addNodeTag($form->getData(), $node);

                    $msg = $this->getTranslator()->trans('node.%node%.linked.tags', [
                        '%node%' => $node->getNodeName(),
                    ]);
                    $this->publishConfirmMessage($request, $msg);

                    $response = new RedirectResponse(
                        $this->getService('urlGenerator')->generate(
                            'nodesEditTagsPage',
                            ['nodeId' => $node->getId()]
                        )
                    );
                    $response->prepare($request);

                    return $response->send();
                }

                $this->assignation['form'] = $form->createView();

                return $this->render('nodes/editTags.html.twig', $this->assignation);
            }
        }

        return $this->throw404();
    }

    /**
     * Return a deletion form for requested tag depending on the node.
     *
     * @param Symfony\Component\HttpFoundation\Requet $request
     * @param int                                     $nodeId
     * @param int                                     $tagId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function removeTagAction(Request $request, $nodeId, $tagId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES_DELETE', $nodeId);

        $node = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\Node', (int) $nodeId);
        $tag = $this->getService('em')
                    ->find('RZ\Roadiz\Core\Entities\Tag', (int) $tagId);

        if ($node !== null && $tag !== null) {
            $this->assignation['node'] = $node;
            $this->assignation['tag'] = $tag;

            $form = $this->buildRemoveTagForm($node, $tag);
            $form->handleRequest();

            if ($form->isValid()) {
                $this->removeNodeTag($form->getData(), $node, $tag);
                $msg = $this->getTranslator()->trans(
                    'tag.%name%.removed',
                    ['%name%' => $tag->getTranslatedTags()->first()->getName()]
                );
                $this->publishConfirmMessage($request, $msg);

                $response = new RedirectResponse(
                    $this->getService('urlGenerator')->generate(
                        'nodesEditTagsPage',
                        ['nodeId' => $node->getId()]
                    )
                );
                $response->prepare($request);

                return $response->send();
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('nodes/removeTag.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Link a node with a tag.
     *
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return RZ\Roadiz\Core\Entities\Tag $linkedTag
     */
    protected function addNodeTag($data, Node $node)
    {
        if (!empty($data['tagPaths'])) {
            $paths = explode(',', $data['tagPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->getService('em')
                            ->getRepository('RZ\Roadiz\Core\Entities\Tag')
                            ->findOrCreateByPath($path);

                $node->addTag($tag);
            }
        }

        $this->getService('em')->flush();

        $this->updateSolrIndex($node);

        return $tag;
    }

    /**
     * @param array                        $data
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param RZ\Roadiz\Core\Entities\Tag  $tag
     *
     * @return RZ\Roadiz\Core\Entities\Tag
     */
    protected function removeNodeTag($data, Node $node, Tag $tag)
    {
        if ($data['nodeId'] == $node->getId() &&
            $data['tagId'] == $tag->getId()) {
            $node->removeTag($tag);
            $this->getService('em')->flush();

            $this->updateSolrIndex($node);

            return $tag;
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildEditTagsForm(Node $node)
    {
        $defaults = [
            'nodeId' => $node->getId(),
        ];
        $builder = $this->getService('formFactory')
                        ->createBuilder('form', $defaults)
                        ->add('nodeId', 'hidden', [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('tagPaths', 'text', [
                            'label' => $this->getTranslator()->trans('list.tags.to_link'),
                            'attr' => ['class' => 'rz-tag-autocomplete'],
                        ])
                        ->add('separator_1', new SeparatorType(), [
                            'label' => $this->getTranslator()->trans('use.new_or_existing.tags_with_hierarchy'),
                            'attr' => ['class' => 'form-help-static uk-alert uk-alert-large'],
                        ]);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Node $node
     * @param RZ\Roadiz\Core\Entities\Tag  $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildRemoveTagForm(Node $node, Tag $tag)
    {
        $builder = $this->getService('formFactory')
                        ->createBuilder('form')
                        ->add('nodeId', 'hidden', [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ])
                        ->add('tagId', 'hidden', [
                            'data' => $tag->getId(),
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
