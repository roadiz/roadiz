<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Doctrine;

use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;

final class ProjectVersionComparator implements Comparator
{
    private Comparator $defaultSorter;

    public function __construct()
    {
        $this->defaultSorter = new AlphabeticalComparator();
    }

    private function getMigrationSuffix(Version $version): string
    {
        if (preg_match("#(?:[a-zA-Z\\]+)([0-9]{14})$#", (string) $version, $mch)) {
            return $mch[0];
        }
        throw new \Exception('Cannot find the migration suffix for ' . $version);
    }

    public function compare(Version $a, Version $b): int
    {
        $prefixA = $this->getMigrationSuffix($a);
        $prefixB = $this->getMigrationSuffix($b);

        return $prefixA <=> $prefixB ?: $this->defaultSorter->compare($a, $b);
    }
}
