<?php
/**
 * leseclaireurs.net - ConsoleServiceProvider.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-02-01
 */
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Console as Console;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['console.commands'] = function (Container $c) {
            return [
                new Console\DispatcherDebugCommand(),
                new Console\ConfigurationDebugCommand(),
                new Console\TranslationsCommand(),
                new Console\TranslationsCreationCommand(),
                new Console\TranslationsDeleteCommand(),
                new Console\TranslationsEnableCommand(),
                new Console\TranslationsDisableCommand(),
                new Console\NodeTypesCommand(),
                new Console\NodeTypesCreationCommand(),
                new Console\NodeTypesDeleteCommand(),
                new Console\NodeTypesAddFieldCommand(),
                new Console\NodesSourcesCommand(),
                new Console\NodesCommand(),
                new Console\NodesCreationCommand(),
                new Console\NodesDetailsCommand(),
                new Console\NodesCleanNamesCommand(),
                new Console\NodeApplyUniversalFieldsCommand(),
                new Console\ThemesCommand(),
                new Console\ThemeAssetsCommand(),
                new Console\ThemeGenerateCommand(),
                new Console\ThemeInstallCommand(),
                new Console\ThemeInfoCommand(),
                new Console\InstallCommand(),
                new Console\UsersCommand(),
                new Console\UsersCreationCommand(),
                new Console\UsersDeleteCommand(),
                new Console\UsersDisableCommand(),
                new Console\UsersEnableCommand(),
                new Console\UsersRolesCommand(),
                new Console\UsersPasswordCommand(),
                new Console\RequirementsCommand(),
                new Console\SolrCommand(),
                new Console\SolrResetCommand(),
                new Console\SolrReindexCommand(),
                new Console\SolrOptimizeCommand(),
                new Console\CacheCommand(),
                new Console\CacheInfosCommand(),
                new Console\CacheFpmCommand(),
                new Console\HtaccessCommand(),
                new Console\DocumentDownscaleCommand(),
                new Console\NodesOrphansCommand(),
                new Console\DatabaseDumpCommand(),
                new Console\FilesExportCommand(),
                new Console\FilesImportCommand(),
                new Console\LogsCleanupCommand(),
                new Console\DocumentSizeCommand(),
            ];
        };
    }
}
