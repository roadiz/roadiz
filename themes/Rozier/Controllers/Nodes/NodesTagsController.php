<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\Nodes;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\Node\NodeTaggedEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Themes\Rozier\Forms\NodeTagsType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Traits\NodesTrait;

/**
 * @package Themes\Rozier\Controllers\Nodes
 */
class NodesTagsController extends RozierApp
{
    use NodesTrait;

    /**
     * Return tags form for requested node.
     *
     * @param Request $request
     * @param int     $nodeId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editTagsAction(Request $request, int $nodeId)
    {
        $this->validateNodeAccessForRole('ROLE_ACCESS_NODES', $nodeId);

        /** @var NodesSources $source */
        $source = $this->get('em')
                       ->getRepository(NodesSources::class)
                       ->setDisplayingAllNodesStatuses(true)
                       ->setDisplayingNotPublishedNodes(true)
                       ->findOneBy([
                           'node.id' => $nodeId,
                           'translation' => $this->get('defaultTranslation')
                       ]);
        if (null === $source) {
            /** @var NodesSources $source */
            $source = $this->get('em')
                ->getRepository(NodesSources::class)
                ->setDisplayingAllNodesStatuses(true)
                ->setDisplayingNotPublishedNodes(true)
                ->findOneBy([
                    'node.id' => $nodeId,
                ]);
        }

        if (null !== $source) {
            $node = $source->getNode();
            $form = $this->createForm(NodeTagsType::class, $node);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->get('em')->flush();
                /*
                 * Dispatch event
                 */
                $this->get('dispatcher')->dispatch(new NodeTaggedEvent($node));

                $msg = $this->getTranslator()->trans('node.%node%.linked.tags', [
                    '%node%' => $node->getNodeName(),
                ]);
                $this->publishConfirmMessage($request, $msg, $source);

                return $this->redirect($this->generateUrl(
                    'nodesEditTagsPage',
                    ['nodeId' => $node->getId()]
                ));
            }

            $this->assignation['translation'] = $source->getTranslation();
            $this->assignation['node'] = $node;
            $this->assignation['source'] = $source;
            $this->assignation['form'] = $form->createView();

            return $this->render('nodes/editTags.html.twig', $this->assignation);
        }

        throw new ResourceNotFoundException();
    }

    /**
     * Link a node with a tag.
     *
     * @param array $data
     * @param Node  $node
     */
    protected function repopulateNodeTags(array $data, Node $node)
    {
        $node->getTags()->clear();

        if (!empty($data['tagPaths'])) {
            $paths = explode(',', $data['tagPaths']);
            $paths = array_filter($paths);

            foreach ($paths as $path) {
                $tag = $this->get('em')
                            ->getRepository(Tag::class)
                            ->findOrCreateByPath($path);

                $node->addTag($tag);
            }
        }

        $this->get('em')->flush();
    }

    /**
     * @param array $data
     * @param Node  $node
     * @param Tag   $tag
     *
     * @return Tag
     */
    protected function removeNodeTag(array $data, Node $node, Tag $tag)
    {
        if ($data['nodeId'] == $node->getId() &&
            $data['tagId'] == $tag->getId()) {
            $node->removeTag($tag);
            $this->get('em')->flush();
        }

        return $tag;
    }

    /**
     * @param Node $node
     * @param Tag  $tag
     *
     * @return FormInterface
     */
    protected function buildRemoveTagForm(Node $node, Tag $tag)
    {
        $builder = $this->createFormBuilder()
                        ->add('nodeId', HiddenType::class, [
                            'data' => $node->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ])
                        ->add('tagId', HiddenType::class, [
                            'data' => $tag->getId(),
                            'constraints' => [
                                new NotNull(),
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
