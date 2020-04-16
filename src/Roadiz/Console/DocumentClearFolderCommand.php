<?php
declare(strict_types=1);
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 * @file DocumentSizeCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Intervention\Image\ImageManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class DocumentClearFolderCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;
    /**
     * @var ImageManager
     */
    private $manager;

    protected function configure()
    {
        $this->setName('documents:clear-folder')
            ->addArgument('folderId', InputArgument::REQUIRED, 'Folder ID to delete documents from.')
            ->setDescription('Delete every document from folder.')
        ;
    }

    protected function getDocumentQueryBuilder(EntityManagerInterface $entityManager, Folder $folder): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = $entityManager->getRepository(Document::class)->createQueryBuilder('d');
        return $qb->innerJoin('d.folders', 'f')
            ->andWhere($qb->expr()->eq('f.id', ':folderId'))
            ->setParameter(':folderId', $folder);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getHelper('entityManager')->getEntityManager();
        $this->io = new SymfonyStyle($input, $output);

        $folderId = (int) $input->getArgument('folderId');
        if ($folderId <= 0) {
            throw new \InvalidArgumentException('Folder ID must be a valid ID');
        }
        /** @var Folder|null $folder */
        $folder = $em->find(Folder::class, $folderId);
        if ($folder === null) {
            throw new \InvalidArgumentException(sprintf('Folder #%d does not exist.', $folderId));
        }

        $batchSize = 20;
        $i = 0;

        $count = $this->getDocumentQueryBuilder($em, $folder)
            ->select('count(d)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($count <= 0) {
            $this->io->warning('No documents were found in this folder.');
            return 0;
        }

        if ($this->io->askQuestion(new ConfirmationQuestion(
            sprintf('Are you sure to delete permanently %d documents?', $count),
            false
        ))) {
            $results = $this->getDocumentQueryBuilder($em, $folder)
                ->select('d')
                ->getQuery()
                ->getResult();

            $this->io->progressStart($count);
            /** @var Document $document */
            foreach ($results as $document) {
                $em->remove($document);
                if (($i % $batchSize) === 0) {
                    $em->flush(); // Executes all updates.
                }
                ++$i;
                $this->io->progressAdvance();
            }
            $em->flush();
            $this->io->progressFinish();
        }

        return 0;
    }
}
