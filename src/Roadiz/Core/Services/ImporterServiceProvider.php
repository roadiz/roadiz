<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ImporterServiceProvider.php
 * @author Ambroise Maupate
 *
 */

/**
 * roadiz - ImporterServiceProvider.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-05-28
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Attribute\Importer\AttributeImporter;
use RZ\Roadiz\CMS\Importers\ChainImporter;
use RZ\Roadiz\CMS\Importers\GroupsImporter;
use RZ\Roadiz\CMS\Importers\NodesImporter;
use RZ\Roadiz\CMS\Importers\NodeTypesImporter;
use RZ\Roadiz\CMS\Importers\RolesImporter;
use RZ\Roadiz\CMS\Importers\SettingsImporter;
use RZ\Roadiz\CMS\Importers\TagsImporter;

class ImporterServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[ChainImporter::class] = $container->factory(function ($c) {
            return new ChainImporter([
                $c[AttributeImporter::class],
                $c[GroupsImporter::class],
                $c[NodesImporter::class],
                $c[NodeTypesImporter::class],
                $c[RolesImporter::class],
                $c[SettingsImporter::class],
                $c[TagsImporter::class],
            ]);
        });

        $container[AttributeImporter::class] = $container->factory(function ($c) {
            return new AttributeImporter($c);
        });
        $container[GroupsImporter::class] = $container->factory(function ($c) {
            return new GroupsImporter($c);
        });
        $container[NodesImporter::class] = $container->factory(function ($c) {
            return new NodesImporter($c);
        });
        $container[NodeTypesImporter::class] = $container->factory(function ($c) {
            return new NodeTypesImporter($c);
        });
        $container[RolesImporter::class] = $container->factory(function ($c) {
            return new RolesImporter($c);
        });
        $container[SettingsImporter::class] = $container->factory(function ($c) {
            return new SettingsImporter($c);
        });
        $container[TagsImporter::class] = $container->factory(function ($c) {
            return new TagsImporter($c);
        });
    }
}
