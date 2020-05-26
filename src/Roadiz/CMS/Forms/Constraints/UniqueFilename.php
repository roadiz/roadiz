<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueFilename
 * @package RZ\Roadiz\CMS\Forms\Constraints
 */
class UniqueFilename extends Constraint
{
    /**
     * @var Document null
     */
    public $document = null;

    /**
     * @var Packages
     */
    public $packages;

    public $message = 'filename.alreadyExists';
}
