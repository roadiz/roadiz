<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file ImporterInterface.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */

namespace RZ\Renzo\CMS\Importers;

/**
 * Class for create all importer.
 */
interface ImporterInterface
{
    /**
     * Import json file.
     *
     * @param string $template
     *
     * @return bool
     */
    public static function importJsonFile($template);
}
