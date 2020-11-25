<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 * @deprecated Use UniqueEntityValidator constraint instead with "folderName" field
 */
class UniqueFolderNameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof UniqueFolderName) {
            /*
             * If value is already the node name
             * do nothing.
             */
            if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
                return;
            }

            if (null !== $constraint->entityManager) {
                if (true === $this->entityExists($value, $constraint->entityManager)) {
                    $this->context->addViolation($constraint->message);
                }
            } else {
                $this->context->addViolation('UniqueFolderNameValidator constraint requires a valid EntityManager');
            }
        }
    }

    /**
     * @param string $name
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function entityExists(string $name, EntityManager $entityManager)
    {
        $entity = $entityManager->getRepository(Folder::class)->findOneByFolderName($name);

        return (null !== $entity);
    }
}
