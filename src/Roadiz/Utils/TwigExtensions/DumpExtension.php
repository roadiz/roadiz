<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Doctrine\Common\Util\Debug;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Extension\DumpExtension as BaseDumpExtension;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DumpExtension extends AbstractExtension
{
    private LoggerInterface $messageCollector;
    private BaseDumpExtension $baseDumpExtension;

    public function __construct(LoggerInterface $messageCollector, ClonerInterface $cloner, HtmlDumper $dumper = null)
    {
        $this->messageCollector = $messageCollector;
        $this->baseDumpExtension = new BaseDumpExtension($cloner, $dumper);
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dump', [$this, 'dump'], ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true]),
        ];
    }

    public function getTokenParsers(): array
    {
        return $this->baseDumpExtension->getTokenParsers();
    }

    public function dump(Environment $env, $context)
    {
        if (!$env->isDebug()) {
            return null;
        }
        $count = \func_num_args();
        for ($i = 2; $i < $count; ++$i) {
            $this->messageCollector->debug(Debug::export(\func_get_arg($i), 2));
        }

        return $this->baseDumpExtension->dump(...\func_get_args());
    }
}
