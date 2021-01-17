<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers\CustomForms;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class CustomFormFieldAttributesController extends RozierApp
{
    /**
     * List every node-types.
     *
     * @param Request $request
     * @param int     $customFormAnswerId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, int $customFormAnswerId)
    {
        $this->denyAccessUnlessGranted('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */

        /** @var CustomFormAnswer $customFormAnswer */
        $customFormAnswer = $this->get("em")->find(CustomFormAnswer::class, $customFormAnswerId);
        $answers = $this->getAnswersByGroups($customFormAnswer->getAnswers());

        $this->assignation['fields'] = $answers;
        $this->assignation['answer'] = $customFormAnswer;
        $this->assignation['customFormId'] = $customFormAnswer->getCustomForm()->getId();

        return $this->render('custom-form-field-attributes/list.html.twig', $this->assignation);
    }

    /**
     * @param Collection|array $answers
     * @return array
     */
    protected function getAnswersByGroups($answers)
    {
        $fieldsArray = [];

        /** @var CustomFormFieldAttribute $answer */
        foreach ($answers as $answer) {
            $groupName = $answer->getCustomFormField()->getGroupName();
            if ($groupName != '') {
                if (!isset($fieldsArray[$groupName])) {
                    $fieldsArray[$groupName] = [];
                }
                $fieldsArray[$groupName][] = $answer;
            } else {
                $fieldsArray[] = $answer;
            }
        }

        return $fieldsArray;
    }
}
