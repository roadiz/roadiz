<?php
declare(strict_types=1);

namespace Themes\Rozier\AjaxControllers;

use RZ\Roadiz\Core\Entities\AttributeValue;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AjaxAttributeValuesController extends AbstractAjaxController
{
    protected static $validMethods = [
        Request::METHOD_POST,
    ];

    /**
     * Handle AJAX edition requests for NodeTypeFields
     * such as coming from widgets.
     *
     * @param Request $request
     * @param int     $attributeValueId
     *
     * @return Response JSON response
     */
    public function editAction(Request $request, int $attributeValueId)
    {
        /*
         * Validate
         */
        $this->validateRequest($request, 'POST', false);
        $this->denyAccessUnlessGranted('ROLE_ACCESS_NODE_ATTRIBUTES');

        /** @var AttributeValue|null $attributeValue */
        $attributeValue = $this->get('em')->find(AttributeValue::class, (int) $attributeValueId);

        if ($attributeValue !== null) {
            $responseArray = [];
            /*
             * Get the right update method against "_action" parameter
             */
            switch ($request->get('_action')) {
                case 'updatePosition':
                    $responseArray = $this->updatePosition($request->request->all(), $attributeValue);
                    break;
            }

            return new JsonResponse(
                $responseArray,
                Response::HTTP_PARTIAL_CONTENT
            );
        }

        throw $this->createNotFoundException($this->getTranslator()->trans(
            'attribute_value.%attributeValueId%.not_exists',
            [
                '%attributeValueId%' => $attributeValueId
            ]
        ));
    }

    /**
     * @param array         $parameters
     * @param AttributeValue $attributeValue
     *
     * @return array
     */
    protected function updatePosition($parameters, AttributeValue $attributeValue): array
    {
        $attributable = $attributeValue->getAttributable();
        $details = [
            '%name%' => $attributeValue->getAttribute()->getLabelOrCode(),
            '%nodeName%' => $attributable instanceof Node ? $attributable->getNodeName() : '',
        ];
        /*
         * First, we set the new parent
         */
        if (!empty($parameters['newPosition'])) {
            $attributeValue->setPosition((float) $parameters['newPosition']);
            // Apply position update before cleaning
            $this->get('em')->flush();
            return [
                'statusCode' => '200',
                'status' => 'success',
                'responseText' => $this->getTranslator()->trans(
                    'attribute_value_translation.%name%.updated_from_node.%nodeName%',
                    $details
                ),
            ];
        }

        return [
            'statusCode' => '400',
            'status' => 'error',
            'responseText' => $this->getTranslator()->trans(
                'attribute_value_translation.%name%.updated_from_node.%nodeName%',
                $details
            ),
        ];
    }
}
