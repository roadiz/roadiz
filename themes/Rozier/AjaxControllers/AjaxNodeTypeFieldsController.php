<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxNodeTypeFieldsController extends AjaxAbstractFieldsController
{
    /**
     * Handle AJAX edition requests for NodeTypeFields
     * such as coming from widgets.
     *
     * @param Request $request
     * @param int     $nodeTypeFieldId
     *
     * @return Response JSON response
     */
    public function editAction(Request $request, int $nodeTypeFieldId)
    {
        /*
         * Validate
         */
        $this->validateRequest($request);
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODEFIELDS_DELETE');

        $field = $this->get('em')->find(NodeTypeField::class, (int) $nodeTypeFieldId);

        if (null !== $response = $this->handleFieldActions($request, $field)) {
            return $response;
        }

        throw $this->createNotFoundException($this->getTranslator()->trans(
            'field.%nodeTypeFieldId%.not_exists',
            [
                '%nodeTypeFieldId%' => $nodeTypeFieldId
            ]
        ));
    }
}
