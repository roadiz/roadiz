<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\UrlAlias;
use RZ\Roadiz\Core\Repositories\NodeRepository;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueNodeNameValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $value = StringHandler::slugify($value);

        /*
         * If value is already the node name
         * do nothing.
         */
        if (null !== $constraint->currentValue && $value == $constraint->currentValue) {
            return;
        }

        if (null !== $constraint->entityManager) {
            if (true === $this->urlAliasExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->messageUrlAlias);
            } elseif (true === $this->nodeNameExists($value, $constraint->entityManager)) {
                $this->context->addViolation($constraint->message);
            }
        } else {
            $this->context->addViolation('UniqueNodeNameValidator constraint requires a valid EntityManager');
        }
    }

    /**
     * @param string $name
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function urlAliasExists($name, $entityManager)
    {
        return (boolean) $entityManager->getRepository(UrlAlias::class)->exists($name);
    }

    /**
     * @param string $name
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    protected function nodeNameExists($name, $entityManager)
    {
        /** @var NodeRepository $nodeRepo */
        $nodeRepo = $entityManager->getRepository(Node::class);
        return (boolean) $nodeRepo->setDisplayingNotPublishedNodes(true)
                                  ->exists($name);
    }
}
