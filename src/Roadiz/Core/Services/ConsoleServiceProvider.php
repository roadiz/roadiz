<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Console as Console;
use Symfony\Bridge\Twig\Command\DebugCommand;
use Symfony\Bridge\Twig\Command\LintCommand;
use Symfony\Component\Translation\Command\XliffLintCommand;

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
                new Console\ThemesListCommand(),
                new Console\ThemeAssetsCommand(),
                new Console\ThemeGenerateCommand(),
                new Console\ThemeInstallCommand(),
                new Console\ThemeInfoCommand(),
                new Console\ThemeRegisterCommand(),
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
                new Console\DocumentFilesizeCommand(),
                new Console\DocumentAverageColorCommand(),
                new Console\DocumentPruneCommand(),
                new Console\DocumentPruneOrphansCommand(),
                new Console\ThemeMigrateCommand(),
                new Console\VersionsPurgeCommand(),
                new Console\GeneratePrivateKeyCommand(),
                new Console\PurgeLoginAttemptCommand(),
                new Console\CleanLoginAttemptCommand(),
                new Console\DocumentClearFolderCommand(),
                new Console\NodeClearTagCommand(),
                new Console\NodesEmptyTrashCommand(),

                new LintCommand($c['twig.environment']),
                new XliffLintCommand(),
                new DebugCommand($c['twig.environment']),
            ];
        };
    }
}
