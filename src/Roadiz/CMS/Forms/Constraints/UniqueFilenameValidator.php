<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 */
class UniqueFilenameValidator extends ConstraintValidator
{
    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

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
            $folder = $this->packages->getDocumentFolderPath($document);

            if ($fs->exists($folder . '/' . $value)) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
