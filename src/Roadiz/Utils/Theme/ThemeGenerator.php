<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Theme;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Config\ConfigurationHandler;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string
     */
    protected $publicDir;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var ConfigurationHandler
     */
    protected $configurationHandler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    const REPOSITORY = 'https://github.com/roadiz/BaseTheme.git';

    /**
     * ThemeGenerator constructor.
     *
     * @param string $projectDir
     * @param string $rootDir
     * @param string $publicDir
     * @param string $cacheDir
     * @param ConfigurationHandler $configurationHandler
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $projectDir,
        string $rootDir,
        string $publicDir,
        string $cacheDir,
        ConfigurationHandler $configurationHandler,
        LoggerInterface $logger
    ) {
        $this->projectDir = $projectDir;
        $this->rootDir = $rootDir;
        $this->publicDir = $publicDir;
        $this->cacheDir = $cacheDir;
        $this->configurationHandler = $configurationHandler;
        $this->logger = $logger;
    }

    /**
     * @param string $classname
     * @return string
     */
    protected function validateThemeClassname(string $classname)
    {
        if (false !== strpos($classname, '\\')) {
            if (null !== $reflection = $this->getThemeReflectionClass($classname)) {
                return $reflection->getName();
            }
            throw new \RuntimeException('Theme class ' . $classname . ' does not exist.');
        }

        if (in_array($classname, ['Default', 'Debug', 'Base', 'Install', 'Rozier'])) {
            $this->getThemePath($classname);
            return $classname;
        }

        if (1 !== preg_match('#^[A-Z][a-zA-Z]+$#', $classname)) {
            throw new \RuntimeException('Theme name must only contain alphabetical characters and begin with uppercase letter.');
        }

        if (1 === preg_match('#[Tt]heme$#', $classname)) {
            throw new \RuntimeException('Theme name must not contain "Theme" suffix, it will be added automatically.');
        }

        $this->getThemePath($this->getThemeName($classname));

        return $classname;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function validateName(string $name): string
    {
        if (1 !== preg_match('#^[A-Z][a-zA-Z]+$#', $name)) {
            throw new LogicException('Theme name must only contain alphabetical characters and begin with uppercase letter.');
        }

        if (1 === preg_match('#[Tt]heme$#', $name)) {
            throw new LogicException('Theme name must not contain "Theme" suffix, it will be added automatically.');
        }

        if ($this->filesystem->exists($this->projectDir . '/themes/' . $name . 'Theme')) {
            throw new LogicException('Theme already exists.');
        }

        if (in_array($name, ['Default', 'Debug', 'Base', 'Install', 'Rozier'])) {
            throw new LogicException('You cannot name your theme after system themes (Default, Install, Base, Rozier or Debug).');
        }

        return $name;
    }

    /**
     * @param string $themePath
     * @param string $branch
     *
     * @return $this
     */
    protected function downloadTheme(string $themePath, string $branch = 'master'): ThemeGenerator
    {
        /*
         * Clone BaseTheme
         */
        $process = new Process(
            ['git', 'clone', '-b', $branch, static::REPOSITORY, $themePath]
        );
        $process->run();
        $this->logger->info('BaseTheme cloned into ' . $themePath);

        return $this;
    }

    /**
     * @param string $themePath
     * @param string $name
     *
     * @return $this
     */
    protected function renameTheme(string $themePath, string $name): ThemeGenerator
    {
        if (!$this->filesystem->exists($themePath)) {
            throw new FileException($themePath . ' theme does not exist.');
        }
        /*
         * Remove existing Git history.
         */
        $this->filesystem->remove($themePath . '/.git');
        $this->logger->info('Remove Git history.');

        /*
         * Rename main theme class.
         */
        $this->filesystem->rename($themePath . '/BaseThemeApp.php', $themePath . '/' . $name . 'ThemeApp.php');
        $this->logger->info('Rename main theme class.');
        $this->filesystem->rename($themePath . '/Services/BaseThemeServiceProvider.php', $themePath . '/Services/' . $name . 'ThemeServiceProvider.php');
        $this->logger->info('Rename theme service provider class.');

        /*
         * Rename every occurrences of BaseTheme in your theme.
         */
        $processes = new ArrayCollection();
        $processes->add(new Process(
            [
                'find', $themePath, '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/BaseTheme/' . $name . 'Theme/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themePath, '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/Base theme/' . $name . ' theme/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themePath . '/static', '-type', 'f', '-exec', 'sed', '-i.bak',
                '-e', 's/Base/' . $name . '/g', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $processes->add(new Process(
            [
                'find', $themePath , '-type', 'f', '-name', '*.bak', '-exec', 'rm', '-f', '{}', ';',
            ],
            null,
            ['LC_ALL' => 'C']
        ));
        $this->logger->info('Rename every occurrences of BaseTheme in your theme.');
        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }

        return $this;
    }

    /**
     * @param string $themeName
     *
     * @return $this
     */
    protected function registerTheme(string $themeName): ThemeGenerator
    {
        $className = '\\Themes\\'.$themeName.'\\'.$themeName. 'App';

        /** @var array $config */
        $config = $this->configurationHandler->getConfiguration();
        $config['themes'][] = [
            'classname' => $className,
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
     * Get real theme path from its name.
     *
     * Attention: theme could be located in vendor folder (/vendor/roadiz/roadiz)
     *
     * @param string $themeName Theme name WITH «Theme» suffix.
     * @return string Theme absolute path.
     */
    protected function getThemePath(string $themeName): string
    {
        if (false !== strpos($themeName, '\\')) {
            if (null !== $themePath = $this->getThemeReflectionClassPath($themeName)) {
                return $themePath;
            }
        }

        if ($this->filesystem->exists($this->projectDir . '/themes/' . $themeName)) {
            return $this->projectDir . '/themes/' . $themeName;
        }

        if ($this->filesystem->exists($this->projectDir . '/vendor/roadiz/roadiz/themes/' . $themeName)) {
            return $this->projectDir . '/vendor/roadiz/roadiz/themes/' . $themeName;
        }

        throw new \RuntimeException('Theme "'.$themeName.'" does not exist in "' . $this->projectDir . '/themes/" nor in ' . $this->projectDir . '/vendor/roadiz/roadiz/themes/ folders.');
    }

    /**
     * @param string $themeName Theme name WITH «Theme» suffix.
     * @return string
     */
    protected function getNewThemePath(string $themeName): string
    {
        return $this->projectDir . '/themes/' . $themeName;
    }

    /**
     * @param string $className
     *
     * @return null|\ReflectionClass
     */
    protected function getThemeReflectionClass(string $className): ?\ReflectionClass
    {
        try {
            $reflection = new \ReflectionClass($className);
            if ($reflection->isSubclassOf(AppController::class)) {
                return $reflection;
            }
        } catch (ReflectionException $Exception) {
            return null;
        }

        return null;
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    protected function getThemeReflectionClassPath(string $className): ?string
    {
        if (null !== $reflection = $this->getThemeReflectionClass($className)) {
            return call_user_func([$className, 'getThemeFolder']);
        }

        return null;
    }

    /**
     * @param string $name Theme name WITHOUT suffix.
     * @return string
     */
    protected function getThemeName(string $name): string
    {
        if (false !== strpos($name, '\\')) {
            if (null !== $reflection = $this->getThemeReflectionClass($name)) {
                return $reflection->getName();
            }
        }

        if (in_array($name, ['Debug', 'Install', 'Rozier'])) {
            return $name;
        }

        return $name . 'Theme';
    }

    /**
     * @param string $themeName
     *
     * @return string
     */
    protected function getThemeFolderName(string $themeName): string
    {
        if (false !== strpos($themeName, '\\')) {
            if (null !== $reflection = $this->getThemeReflectionClass($themeName)) {
                return call_user_func([$reflection->getName(), 'getThemeDir']);
            }
        }

        return $themeName;
    }

    /**
     * @param string $themeName Theme name WITH suffix.
     * @param string $expectedMethod
     * @return string|null
     */
    protected function generateThemeSymlink(string $themeName, string $expectedMethod)
    {
        if ($this->rootDir !== $this->publicDir) {
            $publicThemeDir = $this->publicDir . '/themes/' . $this->getThemeFolderName($themeName);
            $targetDir = $publicThemeDir . '/static';
            $originDir = $this->getThemePath($themeName) . '/static';

            $this->filesystem->remove($publicThemeDir);
            $this->filesystem->mkdir($publicThemeDir);

            if (static::METHOD_RELATIVE_SYMLINK === $expectedMethod) {
                return $this->relativeSymlinkWithFallback($originDir, $targetDir);
            } elseif (static::METHOD_ABSOLUTE_SYMLINK === $expectedMethod) {
                return $this->absoluteSymlinkWithFallback($originDir, $targetDir);
            } else {
                return $this->hardCopy($originDir, $targetDir);
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
    private function relativeSymlinkWithFallback(string $originDir, string $targetDir)
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
    private function absoluteSymlinkWithFallback(string $originDir, string $targetDir)
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
    private function symlink(string $originDir, string $targetDir, bool $relative = false)
    {
        if ($relative) {
            $this->filesystem->mkdir(dirname($targetDir));
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(dirname($targetDir)));
        }
        $this->filesystem->symlink($originDir, $targetDir);
        if (!file_exists($targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir), 0, null, $targetDir);
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
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return static::METHOD_COPY;
    }
}
