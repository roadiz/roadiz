<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCustomFormNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = StringHandler::slugify($value);

        if ($constraint instanceof UniqueCustomFormName) {
            /*
             * If value is already the node name
             * do nothing.
             */
            if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
                return;
            }

            if (null !== $constraint->entityManager) {
                if (true === $this->nameExists($value, $constraint->entityManager)) {
                    $this->context->addViolation($constraint->message);
                }
            } else {
                $this->context->addViolation('UniqueCustomFormNameValidator constraint requires a valid EntityManager');
            }
        }
    }

    /**
     * @param string $name
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function nameExists(string $name, EntityManager $entityManager)
    {
        $entity = $entityManager->getRepository(CustomForm::class)
                             ->findOneBy([
                                 'name' => $name,
                             ]);

        return (null !== $entity);
    }
}
