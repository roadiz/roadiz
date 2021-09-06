<?php
declare(strict_types=1);

namespace Themes\DefaultTheme\Controllers\Blocks;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\DefaultTheme\DefaultThemeApp;

class BasicBlockController extends DefaultThemeApp
{
    /**
     * @param Request $request
     * @param NodesSources $source
     * @param array $assignation
     *
     * @return Response
     */
    public function blockAction(Request $request, NodesSources $source, $assignation)
    {
        $this->prepareNodeSourceAssignation($source, $source->getTranslation());
        $this->assignation = array_merge($this->assignation, $assignation);

        return $this->render('blocks/basicblock.html.twig', $this->assignation);
    }
}
