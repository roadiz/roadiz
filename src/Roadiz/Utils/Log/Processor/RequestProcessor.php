<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Log\Processor;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestProcessor
{
    protected RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $record): array
    {
        if (null !== $request = $this->requestStack->getMasterRequest()) {
            $record['context']['request'] = [
                'url'         => $request->getRequestUri(),
                'ip'          => $request->getClientIp(),
                'http_method' => $request->getMethod(),
                'server'      => $request->getHost(),
                'user_agent'  => $request->headers->get('user-agent'),
                'referrer'    => $request->headers->get('referer'),
                'locale'      => $request->getLocale(),
            ];
        }
        return $record;
    }
}
