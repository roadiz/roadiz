<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\CustomFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Themes\Rozier\AjaxControllers
 */
class AjaxCustomFormFieldsController extends AjaxAbstractFieldsController
{
    /**
     * Handle AJAX edition requests for CustomFormFields
     * such as coming from widgets.
     *
     * @param Request $request
     * @param int     $customFormFieldId
     *
     * @return Response JSON response
     */
    public function editAction(Request $request, int $customFormFieldId)
    {
        /*
         * Validate
         */
        $this->validateRequest($request);
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS_DELETE');

        $field = $this->get('em')->find(CustomFormField::class, (int) $customFormFieldId);

        if (null !== $field && null !== $response = $this->handleFieldActions($request, $field)) {
            return $response;
        }

        throw $this->createNotFoundException($this->getTranslator()->trans(
            'field.%customFormFieldId%.not_exists',
            [
                '%customFormFieldId%' => $customFormFieldId
            ]
        ));
    }
}
