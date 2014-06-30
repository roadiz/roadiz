<?php 

namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
* 
*/
class NodeTypesCommand extends Command
{
	private $dialog;
	
	protected function configure()
	{
		$this
			->setName('core:node:types')
			->setDescription('Manage node-types')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'Node-type name'
			)
			->addOption(
			   'create',
			   null,
			   InputOption::VALUE_NONE,
			   'Create a node-type'
			)
			->addOption(
			   'delete',
			   null,
			   InputOption::VALUE_NONE,
			   'Delete requested node-type'
			)
			->addOption(
			   'update',
			   null,
			   InputOption::VALUE_NONE,
			   'Update requested node-type'
			)
			->addOption(
			   'hide',
			   null,
			   InputOption::VALUE_NONE,
			   'Hide requested node-type'
			)
			->addOption(
			   'show',
			   null,
			   InputOption::VALUE_NONE,
			   'Show requested node-type'
			)
			->addOption(
			   'listFields',
			   null,
			   InputOption::VALUE_NONE,
			   'List requested node-type fields'
			)
			->addOption(
			   'generateEntity',
			   null,
			   InputOption::VALUE_NONE,
			   'Generate requested node-type source entity class'
			)
			->addOption(
			   'generateAllEntities',
			   null,
			   InputOption::VALUE_NONE,
			   'Generate every node-types source entity classes'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$this->dialog = $this->getHelperSet()->get('dialog');
		$text="";
		$name = $input->getArgument('name');

		if ($name) {
			
			$nodetype = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\NodeType')
				->findOneBy(array('name'=>$name));

			if ($nodetype !== null) {
				$text = $nodetype->getOneLineSummary();
				$text .= $nodetype->getFieldsSummary();

				if ($input->getOption('delete')) {
					if ($this->dialog->askConfirmation(
							$output,
							'<question>Are you sure to delete '.$nodetype->getName().' node-type?</question> : ',
							false
						)) {
						
						Kernel::getInstance()->em()->remove($nodetype);
						Kernel::getInstance()->em()->flush();
						$text = '<info>Node-type deleted…</info>'.PHP_EOL;
					}
				}
				else if ($input->getOption('hide')) {
					$nodetype->setVisible(false);
					Kernel::getInstance()->em()->flush();

					$text .= '<info>'.$nodetype->getName()." hidden…</info>".PHP_EOL;
				}
				else if ($input->getOption('show')) {
					$nodetype->setVisible(true);
					Kernel::getInstance()->em()->flush();

					$text .= '<info>'.$nodetype->getName()." showed…</info>".PHP_EOL;
				}
				else if ($input->getOption('listFields')) {
					$text .= $nodetype->getFieldsSummary().PHP_EOL;
				}
				else if ($input->getOption('generateEntity')) {
					$text .= '<info>'.$nodetype->getHandler()->generateSourceEntityClass().'</info>'.PHP_EOL;
				}
			}
			else {
				if ($input->getOption('create')) {

					$text = $this->executeCreation($input, $output);
			
				}
			}
		} else {
			
			$nodetypes = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\NodeType')
				->findAll();

			if (count($nodetypes) > 0) {

				if ($input->getOption('generateAllEntities')) {
					foreach ( $nodetypes as $nt) {
						$text .= '<info>'.$nt->getHandler()->generateSourceEntityClass().'</info>'.PHP_EOL;
					}
				}else {
					$text = '<info>Existing node-types…</info>'.PHP_EOL;
					foreach ( $nodetypes as $nt) {
						$text .= $nt->getOneLineSummary();
					}
				}
			}
			else {
				$text = '<info>No available node-types…</info>'.PHP_EOL;
			}
		}


		$output->writeln($text);
	}

	private function executeCreation(InputInterface $input, OutputInterface $output)
	{
		$name = $input->getArgument('name');

		$nt = new NodeType();
		$nt->setName($name);

		$displayName = $this->dialog->ask(
			$output,
			'<question>Enter your node-type display name</question> : ',
			'Neutral'
		);
		$nt->setDisplayName($displayName);

		$description = $this->dialog->ask(
			$output,
			'<question>Enter your node-type description</question> : ',
			''
		);
		$nt->setDescription($description);
		Kernel::getInstance()->em()->persist($nt);

		$i = 1;
		while (true){
			// FIelds
			$field = new NodeTypeField();
			$field->setPosition($i);
			$fName = $this->dialog->ask(
				$output,
				'<question>[Field '.$i.'] Enter field name</question> (default:title): ',
				'title'
			);
			$field->setName($fName);
			$fLabel = $this->dialog->ask(
				$output,
				'<question>[Field '.$i.'] Enter field label</question> (default:Your title): ',
				'Your title'
			);
			$field->setLabel($fLabel);
			$fType = $this->dialog->ask(
				$output,
				'<question>[Field '.$i.'] Enter field type</question> (default:STRING_T): ',
				'STRING_T'
			);
			$fType = constant('RZ\Renzo\Core\Entities\NodeTypeField::' . $fType);
			$field->setType($fType);

			if ($this->dialog->askConfirmation(
					$output,
					'<question>[Field '.$i.'] Must field be indexed?</question> (yes|No): ',
					false
				)) {
				$field->setIndexed(true);
			}
			// Need to populate each side 
			$nt->getFields()->add($field);
			$field->setNodeType($nt);

			Kernel::getInstance()->em()->persist($field);

			if (!$this->dialog->askConfirmation(
					$output,
					'<question>Do you want to add another field?</question> (Yes|no): ',
					true
				)) {
				break;
			}

			$i++;
		}
		Kernel::getInstance()->em()->flush();

		$nt->getHandler()->updateSchema();

		return '<question>Node type '.$nt->getName().' has been created.</question>';
	}
}