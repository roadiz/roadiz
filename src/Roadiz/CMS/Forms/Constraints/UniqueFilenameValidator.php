<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueFilenameValidator
 * @package RZ\Roadiz\CMS\Forms\Constraints
 */
class UniqueFilenameValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($constraint instanceof UniqueFilename) {
            /** @var Document $document */
            $document = $constraint->document;
            /*
             * If value is already the filename
             * do nothing.
             */
            if (null !== $document &&
                $value == $document->getFilename()) {
                return;
            }

            $fs = new Filesystem();
            $folder = $constraint->packages->getDocumentFolderPath($document);

            if ($fs->exists($folder . '/' . $value)) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
