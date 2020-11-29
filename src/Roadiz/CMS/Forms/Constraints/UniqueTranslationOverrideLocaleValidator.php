<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntity constraint instead with "overrideLocale" field
 */
class UniqueTranslationOverrideLocaleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /*
         * If value is already the node name
         * do nothing.
         */
        if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
            return;
        }

        if (null === $value) {
            return;
        }

        if (null !== $constraint->entityManager) {
            if (true === $this->nameExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->message);
            }
        } else {
            $this->context->addViolation('UniqueTranslationOverrideLocaleValidator constraint requires a valid EntityManager');
        }
    }

    /**
     * @param string $name
     * @param \Doctrine\ORM\EntityManager $entityManager
     *
     * @return bool
     */
    protected function nameExists($name, $entityManager)
    {
        $entity = $entityManager->getRepository(Translation::class)
                                ->findOneBy([
                                    'overrideLocale' => $name,
                                ]);

        return (null !== $entity);
    }
}
