<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file CacheFpmCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing PHP-FPM Cache from terminal.
 */
class CacheFpmCommand extends Command
{
    protected function configure()
    {
        $this->setName('cache:clear-fpm')
            ->setDescription('Clear <info>PHP-FPM</info> cache through a cURL request.')
            ->addOption(
                'domain',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Customize <info>clear_cache.php</info> domain if it is not localhost.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $io = new SymfonyStyle($input, $output);
        $url = 'http://localhost/clear_cache.php';
        $scriptName = 'clear_cache.php';

        if ($input->getOption('domain') != '') {
            $url = 'http://'. $input->getOption('domain') . '/' . $scriptName;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidOptionException('Domain must be a valid domain name.');
        }

        try {
            $client = new Client();
            $client->get($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Roadiz_CLI/'.Kernel::$cmsVersion,
                ],
                'query' => [
                    'env' => ($kernel->isPreview() ? 'preview' : $kernel->getEnvironment()),
                ],
                'allow_redirects' => true,
                'timeout' => 2
            ]);
            if ($io->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $io->note('Call web entry-point: ' . $url);
            }
            $io->success('PHP-FPM caches were cleared for '.$kernel->getEnvironment().' environement.');
        } catch (ConnectException $exception) {
            $io->warning('Cannot reach ' . $url . ' [' . $exception->getCode() . ']');
        } catch (ClientException $exception) {
            $io->warning('Cannot GET ' . $url . ' [' . $exception->getCode() . ']');
        }
        return 0;
    }
}
