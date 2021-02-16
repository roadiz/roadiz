<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\CustomForm;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Forms\CustomFormsType;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Entities\Folder;
use RZ\Roadiz\Utils\Document\AbstractDocumentFactory;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @package RZ\Roadiz\Utils\CustomForm
 */
class CustomFormHelper
{
    const ARRAY_SEPARATOR = ', ';

    protected AbstractDocumentFactory $documentFactory;
    protected EntityManagerInterface $em;
    protected CustomForm $customForm;

    /**
     * @param EntityManagerInterface $em
     * @param CustomForm      $customForm
     * @param AbstractDocumentFactory $documentFactory
     */
    public function __construct(EntityManagerInterface $em, CustomForm $customForm, AbstractDocumentFactory $documentFactory)
    {
        $this->em = $em;
        $this->customForm = $customForm;
        $this->documentFactory = $documentFactory;
    }

    /**
     * Create or update custom-form answer and its attributes from
     * a submitted form data.
     *
     * @param FormInterface         $form
     * @param CustomFormAnswer|null $answer
     * @param string                $ipAddress
     *
     * @return CustomFormAnswer
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function parseAnswerFormData(FormInterface $form, CustomFormAnswer $answer = null, string $ipAddress = "")
    {
        if ($form->isSubmitted() && $form->isValid()) {
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
                    $data = $formField->getData();
                    /*
                    * Create attribute if null.
                    */
                    if (null === $fieldAttr) {
                        $fieldAttr = new CustomFormFieldAttribute();
                        $fieldAttr->setCustomFormAnswer($answer);
                        $fieldAttr->setCustomFormField($customFormField);
                        $this->em->persist($fieldAttr);
                    }

                    if (is_array($data) && isset($data[0]) && $data[0] instanceof UploadedFile) {
                        /** @var UploadedFile $file */
                        foreach ($data as $file) {
                            $this->handleUploadedFile($file, $fieldAttr);
                        }
                    } elseif ($data instanceof UploadedFile) {
                        $this->handleUploadedFile($data, $fieldAttr);
                    } else {
                        $fieldAttr->setValue($this->formValueToString($data));
                    }
                }
            }

            $this->em->flush();
            $this->em->refresh($answer);

            return $answer;
        }

        throw new \InvalidArgumentException('Form must be submitted and validated before begin parsing.');
    }

    /**
     * @param UploadedFile             $file
     * @param CustomFormFieldAttribute $fieldAttr
     *
     * @return CustomFormFieldAttribute
     */
    protected function handleUploadedFile(
        UploadedFile $file,
        CustomFormFieldAttribute $fieldAttr
    ): CustomFormFieldAttribute {
        $this->documentFactory->setFile($file);
        $this->documentFactory->setFolder($this->getDocumentFolderForCustomForm());
        $document = $this->documentFactory->getDocument();
        $fieldAttr->getDocuments()->add($document);
        $fieldAttr->setValue($fieldAttr->getValue() . ', ' . $file->getPathname());
        return $fieldAttr;
    }

    /**
     * @return Folder|null
     */
    protected function getDocumentFolderForCustomForm(): ?Folder
    {
        return $this->em->getRepository(Folder::class)
            ->findOrCreateByPath(
                'custom_forms/' .
                $this->customForm->getCreatedAt()->format('Ymd') . '_' .
                substr($this->customForm->getDisplayName(), 0, 30)
            );
    }

    /**
     * @param FormFactory           $formFactory
     * @param CustomFormAnswer|null $answer
     * @param bool                  $forceExpanded
     * @param array                 $options Options passed to final form
     *
     * @return \Symfony\Component\Form\FormInterface
     * @throws \Exception
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
    private function formValueToString($rawValue): string
    {
        if ($rawValue instanceof \DateTime) {
            return $rawValue->format('Y-m-d H:i:s');
        } elseif (is_array($rawValue)) {
            $values = $rawValue;
            $values = array_map('trim', $values);
            $values = array_map('strip_tags', $values);
            return implode(static::ARRAY_SEPARATOR, $values);
        } else {
            return strip_tags((string) $rawValue);
        }
    }

    /**
     * @param CustomFormAnswer $answer
     * @param CustomFormField $field
     * @return CustomFormFieldAttribute|null
     */
    private function getAttribute(CustomFormAnswer $answer, CustomFormField $field): ?CustomFormFieldAttribute
    {
        /** @var CustomFormFieldAttribute|null $attribute */
        $attribute = $this->em->getRepository(CustomFormFieldAttribute::class)->findOneBy([
            'customFormAnswer' => $answer,
            'customFormField' => $field,
        ]);
        return $attribute;
    }
}
