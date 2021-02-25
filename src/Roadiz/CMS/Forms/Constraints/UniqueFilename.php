<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Validator\Constraint;

/**
 * @package RZ\Roadiz\CMS\Forms\Constraints
 */
class UniqueFilename extends Constraint
{
    /**
     * @var Document null
     */
    public $document = null;

    public $message = 'filename.alreadyExists';
}
