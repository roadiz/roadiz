<?php
declare(strict_types=1);
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ThemesCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use ReflectionClass;
use ReflectionException;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Theme\ThemeResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Command line utils for managing themes from terminal.
 */
class ThemesCommand extends Command
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected function configure()
    {
        $this->setName('themes:list')
            ->setDescription('Installed themes')
            ->addArgument(
                'classname',
                InputArgument::OPTIONAL,
                'Main theme classname (Use / instead of \\ and do not forget starting slash)'
            );
    }

    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $name
     * @return string
     */
    public function validateThemeName(string $name)
    {
        if (false !== strpos($name, '\\')) {
            if (null !== $reflection = $this->getThemeReflectionClass($name)) {
                return $reflection->getName();
            }
            throw new \RuntimeException('Theme class ' . $name . ' does not exist.');
        }

        if (in_array($name, ['Debug', 'Install', 'Rozier'])) {
            $this->getThemePath($name);
            return $name;
        }

        if (1 !== preg_match('#^[A-Z][a-zA-Z]+$#', $name)) {
            throw new \RuntimeException('Theme name must only contain alphabetical characters and begin with uppercase letter.');
        }

        if (1 === preg_match('#[Tt]heme$#', $name)) {
            throw new \RuntimeException('Theme name must not contain "Theme" suffix, it will be added automatically.');
        }

        $this->getThemePath($this->getThemeName($name));

        return $name;
    }

    /**
     * Get real theme path from its name.
     *
     * Attention: theme could be located in vendor folder (/vendor/roadiz/roadiz)
     *
     * @param string $themeName Theme name WITH «Theme» suffix.
     * @return string Theme absolute path.
     */
    protected function getThemePath($themeName)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        if (false !== strpos($themeName, '\\')) {
            if (null !== $themePath = $this->getThemeReflectionClassPath($themeName)) {
                return $themePath;
            }
        }

        if ($this->filesystem->exists($kernel->getProjectDir() . '/themes/' . $themeName)) {
            return $kernel->getProjectDir() . '/themes/' . $themeName;
        }

        if ($this->filesystem->exists($kernel->getProjectDir() . '/vendor/roadiz/roadiz/themes/' . $themeName)) {
            return $kernel->getProjectDir() . '/vendor/roadiz/roadiz/themes/' . $themeName;
        }

        throw new \RuntimeException('Theme "'.$themeName.'" does not exist in "' . $kernel->getProjectDir() . '/themes/" nor in ' . $kernel->getProjectDir() . '/vendor/roadiz/roadiz/themes/ folders.');
    }

    /**
     * @param string $themeName Theme name WITH «Theme» suffix.
     * @return string
     */
    protected function getNewThemePath($themeName)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        return $kernel->getProjectDir() . '/themes/' . $themeName;
    }

    /**
     * @param string $className
     *
     * @return null|ReflectionClass
     */
    protected function getThemeReflectionClass($className)
    {
        try {
            $reflection = new ReflectionClass($className);
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
    protected function getThemeReflectionClassPath($className)
    {
        if (null !== $this->getThemeReflectionClass($className)) {
            return call_user_func([$className, 'getThemeFolder']);
        }

        return null;
    }

    /**
     * @param string $name Theme name WITHOUT suffix.
     * @return string
     */
    protected function getThemeName($name)
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
    protected function getThemeFolderName($themeName)
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
    protected function generateThemeSymlink($themeName, $expectedMethod)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        if ($kernel->getRootDir() !== $kernel->getPublicDir()) {
            $publicThemeDir = $kernel->getPublicDir() . '/themes/' . $this->getThemeFolderName($themeName);
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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var ThemeResolverInterface $themeResolver */
        $themeResolver = $this->getHelper('themeResolver')->getThemeResolver();
        $name = $input->getArgument('classname');

        $tableContent = [];

        if ($name) {
            /*
             * Replace slash by anti-slashes
             */
            $name = str_replace('/', '\\', $name);
            $theme = $themeResolver->findThemeByClass($name);
            $tableContent[] = [
                str_replace('\\', '/', $theme->getClassName()),
                ($theme->isAvailable() ? 'X' : ''),
                ($theme->isBackendTheme() ? 'Backend' : 'Frontend'),
            ];
        } else {
            $themes = $themeResolver->findAll();
            if (count($themes) > 0) {
                /** @var Theme $theme */
                foreach ($themes as $theme) {
                    $tableContent[] = [
                        str_replace('\\', '/', $theme->getClassName()),
                        ($theme->isAvailable() ? 'X' : ''),
                        ($theme->isBackendTheme() ? 'Backend' : 'Frontend'),
                    ];
                }
            } else {
                $io->warning('No available themes');
            }
        }

        $io->table(['Class (with / instead of \)', 'Enabled', 'Type'], $tableContent);
        return 0;
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
    private function relativeSymlinkWithFallback($originDir, $targetDir)
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
    private function absoluteSymlinkWithFallback($originDir, $targetDir)
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
    private function symlink($originDir, $targetDir, $relative = false)
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
    private function hardCopy($originDir, $targetDir)
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));
        return self::METHOD_COPY;
    }
}
