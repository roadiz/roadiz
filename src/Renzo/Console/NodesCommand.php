<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\Entities\Translation;
use Doctrine\ORM\Query\ResultSetMapping;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing nodes from terminal.
 */
class NodesCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('core:nodes')
            ->setDescription('Manage nodes')
            ->addArgument(
                'node-name',
                InputArgument::OPTIONAL,
                'Node name'
            )
            ->addArgument(
                'node-type',
                InputArgument::OPTIONAL,
                'Node-type name'
            )
            ->addArgument(
                'locale',
                InputArgument::OPTIONAL,
                'Translation locale'
            )
            ->addOption(
                'create',
                null,
                InputOption::VALUE_NONE,
                'Create a node'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete requested node'
            )
            ->addOption(
                'update',
                null,
                InputOption::VALUE_NONE,
                'Update requested node'
            )
            ->addOption(
                'hide',
                null,
                InputOption::VALUE_NONE,
                'Hide requested node'
            )
            ->addOption(
                'show',
                null,
                InputOption::VALUE_NONE,
                'Show requested node'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";
        $nodeName = $input->getArgument('node-name');
        $typeName = $input->getArgument('node-type');
        $locale = $input->getArgument('locale');

        if (
            $nodeName &&
            $typeName &&
            $input->getOption('create')
        ) {

            $type = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\NodeType')
                ->findOneBy(array('name'=>$typeName));
            $translation = null;

            if ($locale) {
                $translation = Kernel::getInstance()->em()
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findOneBy(array('locale'=>$locale));
            }

            if ($translation === null) {
                $translation = Kernel::getInstance()->em()
                    ->getRepository('RZ\Renzo\Core\Entities\Translation')
                    ->findOneBy(array(), array('id'=> 'ASC'));
            }

            if ($type !== null &&
                $translation !== null) {
                // Node
                $text = $this->executeNodeCreation($input, $output, $type, $translation);
            } else {

            }

        } elseif ($nodeName) {
            $node = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findOneBy(array('nodeName'=>$nodeName));

            if ($node !== null) {
                $text .= $node->getOneLineSummary().$node->getOneLineSourceSummary();
            } else {
                $text = '<info>Node “'.$nodeName.'” does not exists…</info>'.PHP_EOL;
            }
        } else {
            $nodes = Kernel::getInstance()->em()
                ->getRepository('RZ\Renzo\Core\Entities\Node')
                ->findAll();

            foreach ($nodes as $key => $node) {
                $text .= $node->getOneLineSummary();
            }
        }

        $output->writeln($text);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param NodeType        $type
     * @param Translation     $translation
     *
     * @return string
     */
    private function executeNodeCreation(
        InputInterface $input,
        OutputInterface $output,
        NodeType $type,
        Translation $translation
    ) {
        $text = "";
        $nodeName = $input->getArgument('node-name');
        $node = new Node($type);
        $node->setNodeName($nodeName);
        Kernel::getInstance()->em()->persist($node);

        // Source
        $sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
        $source = new $sourceClass($node, $translation);

        $fields = $type->getFields();

        foreach ($fields as $field) {
            $fValue = $this->dialog->ask(
                $output,
                '<question>[Field '.$field->getLabel().']</question> : ',
                ''
            );
            $setterName = 'set'.ucwords($field->getName());
            $source->$setterName($fValue);
        }

        Kernel::getInstance()->em()->persist($source);
        Kernel::getInstance()->em()->flush();
        $text = '<info>Node “'.$nodeName.'” created…</info>'.PHP_EOL;

        return $text;
    }
}
