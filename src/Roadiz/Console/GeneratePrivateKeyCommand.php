<?php
declare(strict_types=1);
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */
namespace RZ\Roadiz\Console;

use RZ\Crypto\KeyChain\KeyChainInterface;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class GeneratePrivateKeyCommand
 *
 * @package RZ\Roadiz\Console
 */
class GeneratePrivateKeyCommand extends Command
{
    protected function configure()
    {
        $this->setName('generate:private-key')
            ->setDescription('Generate a default private key to encode data in your database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();
        $privateKeyPath = $kernel->get('crypto.absolute_private_key_path');

        if (file_exists($privateKeyPath)) {
            $io->note(sprintf('A private already exists at %s.', $privateKeyPath));
        } else {
            $filename = pathinfo($privateKeyPath, PATHINFO_FILENAME);
            $kernel->get(KeyChainInterface::class)->generate($filename);
            $io->success(sprintf('Private key has been generated in %s', $privateKeyPath));
        }
        return 0;
    }
}
