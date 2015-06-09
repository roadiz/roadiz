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
 * @file InstallCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Console\Tools\YamlConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for installing RZ-CMS v3 from terminal.
 */
class ConfigurationCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('config')
            ->setDescription('Manage configuration from CLI')
            ->addOption(
                'enable-devmode',
                null,
                InputOption::VALUE_NONE,
                'Enable the devMode flag for your application'
            )
            ->addOption(
                'disable-devmode',
                null,
                InputOption::VALUE_NONE,
                'Disable the devMode for your application'
            )
            ->addOption(
                'enable-install',
                null,
                InputOption::VALUE_NONE,
                'Enable the install assistant'
            )
            ->addOption(
                'disable-install',
                null,
                InputOption::VALUE_NONE,
                'Disable the install assistant'
            )
            ->addOption(
                'generate-htaccess',
                null,
                InputOption::VALUE_NONE,
                'Generate .htaccess files to protect critical directories'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = "";

        $configuration = new YamlConfiguration();
        if (false === $configuration->load()) {
            $configuration->setConfiguration($configuration->getDefaultConfiguration());
        }

        if ($input->getOption('enable-devmode')) {
            $configuration->setDevMode(true);
            $configuration->writeConfiguration();

            $text .= '<info>Dev mode has been changed to true</info>' . PHP_EOL;
        }
        if ($input->getOption('disable-devmode')) {
            $configuration->setDevMode(false);
            $configuration->writeConfiguration();

            $text .= '<info>Dev mode has been changed to false</info>' . PHP_EOL;
            $text .= 'Do not forget to empty all cache and purge XCache/APC caches manually.' . PHP_EOL;
        }

        if ($input->getOption('enable-install')) {
            $configuration->setInstall(true);
            $configuration->setDevMode(true);

            $configuration->writeConfiguration();

            $text .= '<info>Install mode has been changed to true</info>' . PHP_EOL;
        }
        if ($input->getOption('disable-install')) {
            $configuration->setInstall(false);
            $configuration->writeConfiguration();

            $text .= '<info>Install mode has been changed to false</info>' . PHP_EOL;
            $text .= 'Do not forget to empty all cache and purge XCache/APC caches manually.' . PHP_EOL;
        }

        if ($input->getOption('generate-htaccess')) {
            $text .= '<info>Generating .htaccess files…</info>' . PHP_EOL;

            // Simple deny access files
            $this->protectFolders([
                "/conf",
                "/src",
                "/samples",
                "/gen-src",
                "/files/fonts",
                "/files/private",
                "/bin",
                "/tests",
                "/cache",
                "/logs",
            ], $text);

            $filePath = ROADIZ_ROOT . "/.htaccess";

            if (file_exists(ROADIZ_ROOT) &&
                !file_exists($filePath)) {
                file_put_contents($filePath, $this->getMainHtaccessContent() . PHP_EOL);
                $text .= '    — ' . $filePath . PHP_EOL;
            } else {
                $text .= '    — Can’t write ' . $filePath . ", file already exists or folder is absent." . PHP_EOL;
            }
        }

        $output->writeln($text);
    }

    protected function protectFolders(array $paths, &$text)
    {
        foreach ($paths as $path) {
            $filePath = ROADIZ_ROOT . $path . "/.htaccess";
            if (file_exists(ROADIZ_ROOT . $path) &&
                !file_exists($filePath)) {
                file_put_contents($filePath, "deny from all" . PHP_EOL);
                $text .= '    — ' . $filePath . PHP_EOL;
            } else {
                $text .= '    — Can’t write ' . $filePath . ", file already exists or folder is absent." . PHP_EOL;
            }
        }
    }

    protected function getMainHtaccessContent()
    {
        return '
# ------------------------------------
# Automatic .htaccess file
# Generated by Roadiz
# ------------------------------------
IndexIgnore *

# ------------------------------------
# EXPIRES CACHING
# ------------------------------------
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/x-javascript "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    # Redirect to www
    #RewriteCond %{HTTP_HOST} !^www\.
    #RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$1 [R=301,L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>' . PHP_EOL;
    }
}
