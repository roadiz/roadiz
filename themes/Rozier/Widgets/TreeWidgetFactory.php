<?php
declare(strict_types=1);

namespace Themes\Rozier\Widgets;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\RequestStack;

final class TreeWidgetFactory
{
    private RequestStack $requestStack;
    private ManagerRegistry $managerRegistry;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(RequestStack $requestStack, ManagerRegistry $managerRegistry)
    {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
    }

    public function createNodeTree(?Node $root = null, ?Translation $translation = null): NodeTreeWidget
    {
        return new NodeTreeWidget(
            $this->requestStack,
            $this->managerRegistry,
            $root,
            $translation
        );
    }

    public function createTagTree(?Tag $root = null): TagTreeWidget
    {
        return new TagTreeWidget(
            $this->requestStack,
            $this->managerRegistry,
            $root
        );
    }

    public function createFolderTree(?Folder $root = null): FolderTreeWidget
    {
        return new FolderTreeWidget(
            $this->requestStack,
            $this->managerRegistry,
            $root
        );
    }
}
