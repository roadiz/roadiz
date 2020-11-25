<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Font;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntityValidator constraint instead with "name" and "variant" fields
 */
class UniqueFontVariantValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof UniqueFontVariant) {
            /*
             * If value is already the node name
             * do nothing.
             */
            if (null !== $constraint->currentName &&
                null !== $constraint->currentVariant &&
                $value->getVariant() == $constraint->currentVariant) {
                return;
            }

            if (null !== $constraint->entityManager) {
                if (true === $this->variantExists($value, $constraint->entityManager)) {
                    $this->context->addViolation($constraint->message);
                }
            } else {
                $this->context->addViolation('UniqueFontVariantValidator constraint requires a valid EntityManager');
            }
        }
    }

    /**
     * @param Font $font
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function variantExists(Font $font, EntityManager $entityManager): bool
    {
        $entity = $entityManager->getRepository(Font::class)
                             ->findOneBy([
                                 'name' => $font->getName(),
                                 'variant' => $font->getVariant(),
                             ]);

        return (null !== $entity);
    }
}
