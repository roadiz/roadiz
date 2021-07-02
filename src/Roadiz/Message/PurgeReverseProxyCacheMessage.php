<?php
declare(strict_types=1);

namespace RZ\Roadiz\Message;

final class PurgeReverseProxyCacheMessage implements AsyncMessage
{
    private int $nodeSourceId;

    /**
     * @param int $nodeSourceId
     */
    public function __construct(int $nodeSourceId)
    {
        $this->nodeSourceId = $nodeSourceId;
    }

    /**
     * @return int
     */
    public function getNodeSourceId(): int
    {
        return $this->nodeSourceId;
    }
}
