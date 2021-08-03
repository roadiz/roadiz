<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TreeWidgetFactory;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxNodeTreeController extends AbstractAjaxController
{
    /**
     * @param Request $request
     * @param int|null    $translationId
     *
     * @return JsonResponse
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getTreeAction(Request $request, ?int $translationId = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODES');

        if (null === $translationId) {
            $translation = $this->get('defaultTranslation');
        } else {
            $translation = $this->get('em')
                                ->find(
                                    Translation::class,
                                    $translationId
                                );
        }

        /** @var NodeTreeWidget|null $nodeTree */
        $nodeTree = null;
        $linkedTypes = [];

        switch ($request->get("_action")) {
            /*
             * Inner node edit for nodeTree
             */
            case 'requestNodeTree':
                if ($request->get('parentNodeId') > 0) {
                    $node = $this->get('em')
                                ->find(
                                    Node::class,
                                    (int) $request->get('parentNodeId')
                                );
                } elseif (null !== $this->getUser()) {
                    $node = $this->get(NodeChrootResolver::class)->getChroot($this->getUser());
                } else {
                    $node = null;
                }

                $nodeTree = $this->get(TreeWidgetFactory::class)->createNodeTree($node, $translation);

                if ($request->get('tagId') &&
                    $request->get('tagId') > 0) {
                    $filterTag = $this->get('em')
                                        ->find(
                                            Tag::class,
                                            (int) $request->get('tagId')
                                        );

                    $nodeTree->setTag($filterTag);
                }

                /*
                 * Filter view with only listed node-types
                 */
                $linkedTypes = $request->get('linkedTypes', []);
                if (is_array($linkedTypes) && count($linkedTypes) > 0) {
                    $linkedTypes = array_filter(array_map(function (string $typeName) {
                        return $this->get('nodeTypesBag')->get($typeName);
                    }, $linkedTypes));

                    $nodeTree->setAdditionalCriteria([
                        'nodeType' => $linkedTypes
                    ]);
                }

                $this->assignation['mainNodeTree'] = false;

                if (true === (boolean) $request->get('stackTree')) {
                    $nodeTree->setStackTree(true);
                }
                break;
            /*
             * Main panel tree nodeTree
             */
            case 'requestMainNodeTree':
                $parent = null;
                if (null !== $this->getUser()) {
                    $parent = $this->get(NodeChrootResolver::class)->getChroot($this->getUser());
                }

                $nodeTree = $this->get(TreeWidgetFactory::class)->createNodeTree($parent, $translation);
                $this->assignation['mainNodeTree'] = true;
                break;
        }

        $this->assignation['nodeTree'] = $nodeTree;
        // Need to expose linkedTypes to add data-attributes on widget again
        $this->assignation['linkedTypes'] = $linkedTypes;

        $responseArray = [
            'statusCode' => '200',
            'status' => 'success',
            'linkedTypes' => array_map(function(NodeType $nodeType) {
                return $nodeType->getName();
            }, $linkedTypes),
            'nodeTree' => trim($this->getTwig()->render('widgets/nodeTree/nodeTree.html.twig', $this->assignation)),
        ];

        return new JsonResponse(
            $responseArray
        );
    }
}
