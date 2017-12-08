<?php
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

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Theme;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @var EntityManager
     */
    protected $entityManager;

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

        if (!defined('ROADIZ_ROOT')) {
            throw new \RuntimeException('ROADIZ_ROOT constant should be defined to point to your project root directory.');
        }
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
        if ($this->filesystem->exists(ROADIZ_ROOT . '/themes/' . $themeName)) {
            return ROADIZ_ROOT . '/themes/' . $themeName;
        }

        if ($this->filesystem->exists(ROADIZ_ROOT . '/vendor/roadiz/roadiz/themes/' . $themeName)) {
            return ROADIZ_ROOT . '/vendor/roadiz/roadiz/themes/' . $themeName;
        }

        throw new \RuntimeException('Theme "'.$themeName.'" does not exist in "' . ROADIZ_ROOT . '/themes/" nor in ' . ROADIZ_ROOT . '/vendor/roadiz/roadiz/themes/ folders.');
    }

    /**
     * @param string $themeName Theme name WITH «Theme» suffix.
     * @return string
     */
    protected function getNewThemePath($themeName)
    {
        return ROADIZ_ROOT . '/themes/' . $themeName;
    }

    /**
     * @param string $name Theme name WITHOUT suffix.
     * @return string
     */
    protected function getThemeName($name)
    {
        if (in_array($name, ['Debug', 'Install', 'Rozier'])) {
            return $name;
        }
        return $name . 'Theme';
    }

    /**
     * @param string $themeName Theme name WITH suffix.
     * @param string $expectedMethod
     * @return string
     */
    protected function generateThemeSymlink($themeName, $expectedMethod)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        if ($kernel->getRootDir() !== $kernel->getPublicDir()) {
            $publicThemeDir = $kernel->getPublicDir() . '/themes/' . $themeName;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";
        $name = $input->getArgument('classname');

        $table = new Table($output);
        $table->setHeaders(['Class (with / instead of \)', 'Enabled', 'Type']);
        $tableContent = [];

        if ($name) {
            /*
             * Replace slash by anti-slashes
             */
            $name = str_replace('/', '\\', $name);
            $theme = $this->entityManager->getRepository(Theme::class)
                ->findOneBy(['className' => $name]);
            $tableContent[] = [
                str_replace('\\', '/', $theme->getClassName()),
                ($theme->isAvailable() ? 'X' : ''),
                ($theme->isBackendTheme() ? 'Backend' : 'Frontend'),
            ];
        } else {
            $themes = $this->entityManager
                ->getRepository(Theme::class)
                ->findAll();

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
                $text = '<info>No available themes</info>' . PHP_EOL;
            }
        }
        $table->setRows($tableContent);
        $table->render();
        $output->writeln($text);
    }

    /**
     * Try to create relative symlink.
     *
     * Falling back to absolute symlink and finally hard copy.
     *
     * @param $originDir
     * @param $targetDir
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
     * @param $originDir
     * @param $targetDir
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
     * @param $originDir
     * @param $targetDir
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
     * @param $originDir
     * @param $targetDir
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
