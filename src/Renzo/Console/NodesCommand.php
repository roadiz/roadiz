<?php 

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
* 
*/
class NodesCommand extends Command
{
	private $dialog;

	protected function configure()
	{
		$this
			->setName('core:nodes')
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
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$this->dialog = $this->getHelperSet()->get('dialog');
		$text="";
		$nodeName = $input->getArgument('node-name');
		$typeName = $input->getArgument('node-type');
		$locale = $input->getArgument('locale');

		if ($nodeName && 
			$typeName && 
		    $input->getOption('create')) {

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
				$node = new Node( $type );
				$node->setNodeName($nodeName);
				Kernel::getInstance()->em()->persist($node);

				// Source
				$sourceClass = "GeneratedNodeSources\\".$type->getSourceEntityClassName();
				$source = new $sourceClass();
				Kernel::getInstance()->em()->persist($source);
				Kernel::getInstance()->em()->flush();

				// Joint
				$nodesSourcesJoint = new NodesSources($node, $source, $translation);
				Kernel::getInstance()->em()->persist($nodesSourcesJoint);
				Kernel::getInstance()->em()->flush();

				$text = '<info>Node “'.$nodeName.'” created…</info>'.PHP_EOL;
			}
			else {

			}
		}
		elseif($nodeName) {
			$node = Kernel::getInstance()->em()
				->getRepository('RZ\Renzo\Core\Entities\Node')
				->findOneBy(array('nodeName'=>$nodeName));


			if ($node !== null) {
				$type = $node->getNodeType();
				$text = $node->getOneLineSummary();

				// Equivalent DQL query: "select u from User u where u.name=?1"
				// User is a mapped base class for other classes. User owns no associations.
				$rsm = new ResultSetMapping;
				$rsm->addEntityResult('RZ\Renzo\Core\Entities\Node', 'n');
				$rsm->addJoinedEntityResult('RZ\Renzo\Core\Entities\NodesSources', 'ns', 'n', 'nodeSources');
				$rsm->addJoinedEntityResult('GeneratedNodeSources\\'.$type->getSourceEntityClassName() , 's', 'n', 'source');

				$query = Kernel::getInstance()->em()->createNativeQuery('SELECT n.*, ns.*, s.* FROM Node AS n INNER JOIN NodesSources AS ns ON ns.node_id = n.id INNER JOIN '.$type->getSourceEntityTableName().' AS s ON ns.source_id = s.id WHERE n.node_name = ?', $rsm);
				$query->setParameter(1, $nodeName);

				$resultNode = $query->getResult();
				ob_start();
				var_dump($resultNode);
				$text .= ob_get_clean();
			}
			else {
				$text = '<info>Node “'.$nodeName.'” does not exists…</info>'.PHP_EOL;
			}
		}
		else {

		}

		$output->writeln($text);
	}
}