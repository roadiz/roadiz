<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Message;

use RZ\Roadiz\Message\AsyncMessage;

abstract class AbstractSolrMessage implements AsyncMessage
{
    /**
     * @var class-string
     */
    protected string $classname;
    /**
     * @var mixed
     */
    protected $identifier;

    /**
     * @param string $classname
     * @param mixed $identifier
     */
    public function __construct(string $classname, $identifier)
    {
        $this->classname = $classname;
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
