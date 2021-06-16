<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Theme;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Config\ConfigurationHandlerInterface;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Process\Process;

class ThemeGenerator
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';
    const REPOSITORY = 'https://github.com/roadiz/BaseTheme.git';

    protected Filesystem $filesystem;
    protected string $projectDir;
    protected string $publicDir;
    protected string $cacheDir;
    protected ConfigurationHandlerInterface $configurationHandler;
    protected LoggerInterface $logger;

    /**
     * @param string $projectDir
     * @param string $publicDir
     * @param string $cacheDir
     * @param ConfigurationHandlerInterface $configurationHandler
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        string $projectDir,
        string $publicDir,
        string $cacheDir,
        ConfigurationHandlerInterface $configurationHandler,
        ?LoggerInterface $logger = null
    ) {
        $this->filesystem = new Filesystem();
        $this->projectDir = $projectDir;
        $this->publicDir = $publicDir;
        $this->cacheDir = $cacheDir;
        $this->configurationHandler = $configurationHandler;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param ThemeInfo $themeInfo
     * @param string    $branch
     *
     * @return $this
     */
    public function downloadTheme(ThemeInfo $themeInfo, string $branch = 'master'): ThemeGenerator
    {
        if (!$themeInfo->exists()) {
            /*
             * Clone BaseTheme
             */
            $process = new Process(
                ['git', 'clone', '-b', $branch, static::REPOSITORY, $themeInfo->getThemePath()]
            );
            $process->run();
            $this->logger->info('BaseTheme cloned into ' . $themeInfo->getThemePath());
        } else {
            $this->logger->info($themeInfo->getClassname() . ' already exists.');
        }

        return $this;
    }

    /**
     * @param ThemeInfo $themeInfo
     *
     * @return $this
     */
    public function renameTheme(ThemeInfo $themeInfo): ThemeGenerator
    {
        if (!$themeInfo->exists()) {
            throw new FileException($themeInfo->getThemePath() . ' theme does not exist.');
        }
        if ($themeInfo->isProtected()) {
            throw new \InvalidArgumentException(
                $themeInfo->getThemeName() . ' is protected and cannot renamed.'
            );
        }
        /*
         * Remove existing Git history.
         */
        $this->filesystem->remove($themeInfo->getThemePath() . '/.git');
        $this->logger->info('Remove Git history.');

        /*
         * Rename main theme class.
         */
        $mainClassFile = $themeInfo->getThemePath() . '/' . $themeInfo->getThemeName() . 'App.php';
        if (!$this->filesystem->exists($mainClassFile)) {
            $this->filesystem->rename(
                $themeInfo->getThemePath() . '/BaseThemeApp.php',
                $mainClassFile
            );
            /*
             * Force Zend OPcache to reset file
             */
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($mainClassFile, true);
            }
            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
            }
            $this->logger->info('Rename main theme class.');
        }

        $serviceProviderFile = $themeInfo->getThemePath() .
            '/Services/' . $themeInfo->getThemeName() . 'ServiceProvider.php';
        if (!$this->filesystem->exists($serviceProviderFile)) {
            $this->filesystem->rename(
                $themeInfo->getThemePath() . '/Services/BaseThemeServiceProvider.php',
                $serviceProviderFile
            );
            /*
             * Force Zend OPcache to reset file
             */
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($serviceProviderFile, true);
            }
            if (function_exists('apcu_clear_cache')) {
                apcu_clear_cache();
            }
            $this->logger->info('Rename theme service provider class.');
        }

        /*
         * Rename every occurrences of BaseTheme in your theme.
         */
        $processes = new ArrayCollection();
        $processes->add(new Process(
            [
                'find', $themeInfo->getThemePath(), '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/BaseTheme/' . $themeInfo->getThemeName() . '/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themeInfo->getThemePath(), '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/Base theme/' . $themeInfo->getName() . ' theme/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themeInfo->getThemePath() . '/static', '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/Base/' . $themeInfo->getName() . '/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themeInfo->getThemePath() , '-type', 'f', '-name', '*.bak', '-exec', 'rm', '-f', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $this->logger->info('Rename every occurrences of BaseTheme in your theme.');
        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }

        $cacheClearer = new OPCacheClearer();
        $cacheClearer->clear();

        return $this;
    }

    /**
     * @param ThemeInfo $themeInfo
     *
     * @return $this
     */
    public function registerTheme(ThemeInfo $themeInfo): ThemeGenerator
    {
        if ($themeInfo->isProtected()) {
            throw new \InvalidArgumentException(
                $themeInfo->getThemeName() . ' is protected and cannot be registered.'
            );
        }
        $config = $this->configurationHandler->load();
        /*
         * Checks if theme is not already registered
         */
        foreach ($config['themes'] as $themeParams) {
            if ($themeParams['classname'] === $themeInfo->getClassname()) {
                $this->logger->info($themeInfo->getClassname() . ' is already registered.');
                return $this;
            }
        }

        $config['themes'][] = [
            'classname' => $themeInfo->getClassname(),
            'hostname' => '*',
            'routePrefix' => '',
        ];

        $this->configurationHandler->setConfiguration($config);
        $this->configurationHandler->writeConfiguration();
        /*
         * Need to clear configuration cache.
         */
        $configurationClearer = new ConfigurationCacheClearer($this->cacheDir);
        $configurationClearer->clear();

        return $this;
    }

    /**
     * @param ThemeInfo $themeInfo
     * @param string    $expectedMethod
     *
     * @return string|null
     */
    public function installThemeAssets(ThemeInfo $themeInfo, string $expectedMethod): ?string
    {
        if ($themeInfo->exists()) {
            $publicThemeDir = $this->publicDir . '/themes/' . $themeInfo->getThemeName();
            if ($publicThemeDir !== $themeInfo->getThemePath()) {
                $targetDir = $publicThemeDir . '/static';
                $originDir = $themeInfo->getThemePath() . '/static';

                $this->filesystem->remove($publicThemeDir);
                $this->filesystem->mkdir($publicThemeDir);

                if (static::METHOD_RELATIVE_SYMLINK === $expectedMethod) {
                    return $this->relativeSymlinkWithFallback($originDir, $targetDir);
                } elseif (static::METHOD_ABSOLUTE_SYMLINK === $expectedMethod) {
                    return $this->absoluteSymlinkWithFallback($originDir, $targetDir);
                } else {
                    return $this->hardCopy($originDir, $targetDir);
                }
            } else {
                $this->logger->info($themeInfo->getThemeName() . ' assets are already public.');
            }
        }
        return null;
    }

    /**
     * Try to create relative symlink.
     *
     * Falling back to absolute symlink and finally hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     * @return string
     */
    private function relativeSymlinkWithFallback(string $originDir, string $targetDir): string
    {
        try {
            $this->symlink($originDir, $targetDir, true);
            $method = self::METHOD_RELATIVE_SYMLINK;
        } catch (IOException $e) {
            $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
        }
        return $method;
    }

    /**
     * Try to create absolute symlink.
     *
     * Falling back to hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     * @return string
     */
    private function absoluteSymlinkWithFallback(string $originDir, string $targetDir): string
    {
        try {
            $this->symlink($originDir, $targetDir);
            $method = self::METHOD_ABSOLUTE_SYMLINK;
        } catch (IOException $e) {
            // fall back to copy
            $method = $this->hardCopy($originDir, $targetDir);
        }
        return $method;
    }

    /**
     * Creates symbolic link.
     *
     * @param string $originDir
     * @param string $targetDir
     * @param bool $relative
     */
    private function symlink(string $originDir, string $targetDir, bool $relative = false): void
    {
        if ($relative) {
            $this->filesystem->mkdir(dirname($targetDir));
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(dirname($targetDir)));
        }
        $this->filesystem->symlink($originDir, $targetDir);
        if (!file_exists($targetDir)) {
            throw new IOException(
                sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir),
                0,
                null,
                $targetDir
            );
        }
    }

    /**
     * Copies origin to target.
     *
     * @param string $originDir
     * @param string $targetDir
     * @return string
     */
    private function hardCopy(string $originDir, string $targetDir): string
    {
        try {
            $this->filesystem->mkdir($targetDir, 0777);
            // We use a custom iterator to ignore VCS files
            $this->filesystem->mirror(
                $originDir,
                $targetDir,
                Finder::create()->ignoreDotFiles(false)->in($originDir)
            );
        } catch (IOException $exception) {
            // Do nothing
        }

        return static::METHOD_COPY;
    }
}
