<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function Symfony\Component\String\u;

class CentralTruncateExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter(
                'centralTruncate',
                [$this, 'centralTruncate']
            ),
            new TwigFilter(
                'central_truncate',
                [$this, 'centralTruncate']
            )
        ];
    }

    /**
     * @param string|null $object
     * @param int $length
     * @param int $offset
     * @param string $ellipsis
     * @return string|null
     */
    public function centralTruncate(?string $object, int $length, int $offset = 0, string $ellipsis = '[…]'): ?string
    {
        if (null === $object) {
            return null;
        }
        $unicode = u($object);
        $unicodeEllipsis = u($ellipsis);
        $halfLength = ceil($length / 2);
        $halfOffset = ceil($offset / 2);
        if ($unicode->length() > ($length + $unicodeEllipsis->length()) && $halfLength > $halfOffset) {
            $str1 = $unicode->slice(0, (int)($halfLength + $halfOffset));
            $str2 = $unicode->slice((int)(($halfLength * -1) + $halfOffset));
            return $str1 . $ellipsis . $str2;
        }

        return $object;
    }
}
