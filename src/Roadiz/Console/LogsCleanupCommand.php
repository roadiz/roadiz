<?php
declare(strict_types=1);
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
 * @file LogsCleanupCommand.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Repositories\LogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsCleanupCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('logs:cleanup')
            ->setDescription('Clean up logs entries <info>older than 6 months</info> from database.')
            ->addOption('erase', null, InputOption::VALUE_NONE, 'Actually delete outdated log entries.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime('now');
        $now->add(\DateInterval::createFromDateString('-6 months'));
        $io = new SymfonyStyle($input, $output);

        /** @var EntityManager $em */
        $em = $this->getHelper('em')->getEntityManager();
        /** @var LogRepository $logRepository */
        $logRepository = $em->getRepository(Log::class);
        $qb = $logRepository->createQueryBuilder('l');
        $qb->select($qb->expr()->count('l'))
            ->andWhere($qb->expr()->lte('l.datetime', ':date'))
            ->setParameter(':date', $now)
        ;

        try {
            $logs = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            $logs = 0;
        }

        $io->note($logs . ' log entries found before '. $now->format('Y-m-d') . '.');

        if ($input->getOption('erase') && $logs > 0) {
            $qb2 = $logRepository->createQueryBuilder('l');
            $qb2->delete()
                ->andWhere($qb->expr()->lte('l.datetime', ':date'))
                ->setParameter(':date', $now)
            ;
            try {
                $numDeleted = $qb2->getQuery()->execute();
                $io->success($numDeleted.' log entries were deleted.');
            } catch (NoResultException $e) {
                $io->writeln('No log entries were deleted.');
            }
        }
        return 0;
    }
}
