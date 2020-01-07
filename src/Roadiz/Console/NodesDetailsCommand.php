<?php
declare(strict_types=1);
/**
 * Copyright (c) 2016.
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
 * @file NodesDetailsCommand.php
 * @author ambroisemaupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NodesDetailsCommand extends Command
{
    /** @var  EntityManager */
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodes:show')
            ->setDescription('Show node details and data.')
            ->addArgument('nodeName', InputArgument::REQUIRED, 'Node name to show')
            ->addArgument('locale', InputArgument::REQUIRED, 'Translation locale to use')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();

        $translation = $this->entityManager->getRepository(Translation::class)
                                           ->findOneBy(['locale' => $input->getArgument('locale')]);

        /** @var NodesSources $source */
        $source = $this->entityManager->getRepository(NodesSources::class)
                                    ->setDisplayingNotPublishedNodes(true)
                                    ->findOneBy([
                                        'node.nodeName' => $input->getArgument('nodeName'),
                                        'translation' => $translation,
                                    ]);
        if (null !== $source) {
            $io->title(get_class($source));
            $io->title('Title');
            $io->text($source->getTitle());

            /** @var NodeTypeField $field */
            foreach ($source->getNode()->getNodeType()->getFields() as $field) {
                if (!$field->isVirtual()) {
                    $getter = $field->getGetterName();
                    $data = $source->$getter();

                    if (is_array($data)) {
                        $data = implode(', ', $data);
                    }
                    if ($data instanceof \DateTime) {
                        $data = $data->format('c');
                    }
                    if ($data instanceof \stdClass) {
                        $data = json_encode($data);
                    }

                    if (!empty($data)) {
                        $io->title($field->getLabel());
                        $io->text($data);
                    }
                }
            }
        } else {
            $io->error('No node found.');
            return 1;
        }
        return 0;
    }
}
