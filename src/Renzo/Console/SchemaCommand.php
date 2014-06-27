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
class SchemaCommand extends Command {
	private $dialog;
	
	protected function configure()
	{
		$this
			->setName('schema')
			->setDescription('Manage database schema')
			/*->addOption(
			   'create',
			   null,
			   InputOption::VALUE_NONE,
			   'Create your database schema'
			)*/
			/*->addOption(
			   'drop',
			   null,
			   InputOption::VALUE_NONE,
			   'Drop current database'
			)*/
			->addOption(
			   'update',
			   null,
			   InputOption::VALUE_NONE,
			   'Update current database schema'
			)
			->addOption(
			   'execute',
			   null,
			   InputOption::VALUE_NONE,
			   'Apply changes'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->dialog = $this->getHelperSet()->get('dialog');
		$text="";

		if ($input->getOption('update')) {
			
			$sql = static::getUpdateSchema();
			$count = count($sql);

			if ($count > 0) {
				/*
				 * Print changes
				 */
				for($i=0; $i<$count; $i++) {
				    $text .= $sql[$i].PHP_EOL;
				}
				$text .= '<info>'.$count.'</info> change(s) in your database schema… Use <info>--execute</info> to apply'.PHP_EOL;

				/*
				 * If execute option = Perform changes
				 */
				if ($input->getOption('execute')) {
					if ($this->dialog->askConfirmation(
							$output,
							'<question>Are you sure to update your database schema?</question> : ',
							false
						)) {
				
						if (static::updateSchema()) {
							$text .= '<info>Schema updated…</info>'.PHP_EOL;
						}
					}
				}
			}
			else {
				$text .= '<info>Your database schema is already up to date…</info>'.PHP_EOL;
			}
		}

		$output->writeln($text);
	}

	public static function updateSchema()
	{
		$tool = new \Doctrine\ORM\Tools\SchemaTool( Kernel::getInstance()->em() );
		$meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();
		
		$sql = $tool->getUpdateSchemaSql($meta);

		$count = count($sql);
		for($i=0; $i<$count; $i++) {
		    if(substr($sql[$i], 0, 4) == 'DROP')
		        unset($sql[$i]);
		}

		foreach($sql as $statement) {
		    Kernel::getInstance()->em()->getConnection()->exec( $statement );
		}

		return true;
	}
	public static function getUpdateSchema()
	{
		$tool = new \Doctrine\ORM\Tools\SchemaTool( Kernel::getInstance()->em() );
		$meta = Kernel::getInstance()->em()->getMetadataFactory()->getAllMetadata();
		return $tool->getUpdateSchemaSql($meta);
	}
}