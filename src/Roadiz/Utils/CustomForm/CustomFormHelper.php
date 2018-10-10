<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
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
 * @file CustomFormHelper.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\CustomForm;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\CMS\Forms\CustomFormsType;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

/**
 * Class CustomFormHelper
 * @package RZ\Roadiz\Utils\CustomForm
 */
class CustomFormHelper
{
    const ARRAY_SEPARATOR = ', ';

    /** @var EntityManager */
    private $em;

    /** @var CustomForm */
    private $customForm;

    /**
     * CustomFormHelper constructor.
     * @param EntityManager $em
     * @param CustomForm $customForm
     */
    public function __construct(EntityManager $em, CustomForm $customForm)
    {
        $this->em = $em;
        $this->customForm = $customForm;
    }

    /**
     * Create or update custom-form answer and its attributes from
     * a submitted form data.
     *
     * @param FormInterface $form
     * @param CustomFormAnswer|null $answer
     * @param string $ipAddress
     * @return CustomFormAnswer
     */
    public function parseAnswerFormData(FormInterface $form, CustomFormAnswer $answer = null, $ipAddress = "")
    {
        if ($form->isValid()) {
            /*
             * Create answer if null.
             */
            if (null === $answer) {
                $answer = new CustomFormAnswer();
                $answer->setCustomForm($this->customForm);
                $this->em->persist($answer);
            }
            $answer->setSubmittedAt(new \DateTime());
            $answer->setIp($ipAddress);

            /** @var CustomFormField $customFormField */
            foreach ($this->customForm->getFields() as $customFormField) {
                $formField = null;
                $fieldAttr = null;

                /*
                 * Get data in form groups
                 */
                if ($customFormField->getGroupName() != '') {
                    $groupCanonical = StringHandler::slugify($customFormField->getGroupName());
                    $formGroup = $form->get($groupCanonical);
                    if ($formGroup->has($customFormField->getName())) {
                        $formField = $formGroup->get($customFormField->getName());
                        $fieldAttr = $this->getAttribute($answer, $customFormField);
                    }
                } else {
                    if ($form->has($customFormField->getName())) {
                        $formField = $form->get($customFormField->getName());
                        $fieldAttr = $this->getAttribute($answer, $customFormField);
                    }
                }

                if (null !== $formField) {
                    /*
                    * Create attribute if null.
                    */
                    if (null === $fieldAttr) {
                        $fieldAttr = new CustomFormFieldAttribute();
                        $fieldAttr->setCustomFormAnswer($answer);
                        $fieldAttr->setCustomFormField($customFormField);
                        $this->em->persist($fieldAttr);
                    }

                    $fieldAttr->setValue($this->formValueToString($formField->getData()));
                }
            }

            $this->em->flush();
            $this->em->refresh($answer);

            return $answer;
        }

        throw new \InvalidArgumentException('Form must be submitted and validated before begin parsing.');
    }

    /**
     * @param FormFactory $formFactory
     * @param CustomFormAnswer $answer
     * @param bool $forceExpanded
     * @param array $options Options passed to final form
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getFormFromAnswer(
        FormFactory $formFactory,
        CustomFormAnswer $answer = null,
        $forceExpanded = false,
        array $options = []
    ) {
        $data = null;

        if (null !== $answer) {
            $data = [];
            /** @var CustomFormFieldAttribute $attribute */
            foreach ($answer->getAnswers() as $attribute) {
                $type = $attribute->getCustomFormField()->getType();
                $name = $attribute->getCustomFormField()->getName();

                switch ($type) {
                    case AbstractField::DATE_T:
                    case AbstractField::DATETIME_T:
                        $data[$name] = new \DateTime($attribute->getValue());
                        break;
                    case AbstractField::BOOLEAN_T:
                        $data[$name] = (boolean) $attribute->getValue();
                        break;
                    case AbstractField::MULTIPLE_T:
                    case AbstractField::CHECK_GROUP_T:
                        $data[$name] = explode(static::ARRAY_SEPARATOR, $attribute->getValue());
                        break;
                    default:
                        $data[$name] = $attribute->getValue();
                }
            }
        }

        return $formFactory->create(CustomFormsType::class, $data, array_merge($options, [
            'customForm' => $this->customForm,
            'forceExpanded' => $forceExpanded,
        ]));
    }

    /**
     * @param mixed $rawValue
     * @return string
     */
    private function formValueToString($rawValue)
    {
        if ($rawValue instanceof \DateTime) {
            return $rawValue->format('Y-m-d H:i:s');
        } elseif (is_array($rawValue)) {
            $values = $rawValue;
            $values = array_map('trim', $values);
            $values = array_map('strip_tags', $values);
            return implode(static::ARRAY_SEPARATOR, $values);
        } else {
            return strip_tags($rawValue);
        }
    }

    /**
     * @param CustomFormAnswer $answer
     * @param CustomFormField $field
     * @return null|CustomFormFieldAttribute
     */
    private function getAttribute(CustomFormAnswer $answer, CustomFormField $field)
    {
        /** @var EntityRepository $repo */
        $repo = $this->em->getRepository(CustomFormFieldAttribute::class);
        return $repo->findOneBy([
            'customFormAnswer' => $answer,
            'customFormField' => $field,
        ]);
    }
}
