<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCustomFormFieldNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = StringHandler::variablize($value);

        if ($constraint instanceof UniqueCustomFormFieldName) {
            /*
             * If value is already the node name
             * do nothing.
             */
            if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
                return;
            }

            if (null !== $constraint->entityManager &&
                null !== $constraint->customForm) {
                if (true === $this->nameExists($value, $constraint->customForm, $constraint->entityManager)) {
                    $this->context->addViolation($constraint->message);
                }
            } else {
                $this->context->addViolation('UniqueCustomFormFieldNameValidator constraint requires a valid EntityManager');
            }
        }
    }

    /**
     * @param string $name
     * @param CustomForm $customForm
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function nameExists(string $name, CustomForm $customForm, EntityManager $entityManager)
    {
        $entity = $entityManager->getRepository(CustomFormField::class)
                             ->findOneBy([
                                 'name' => $name,
                                 'customForm' => $customForm,
                             ]);

        return (null !== $entity);
    }
}
