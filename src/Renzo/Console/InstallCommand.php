<?php 


namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Console\SchemaCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
* 
*/
class InstallCommand extends Command {
	private $dialog;
	
	protected function configure()
	{
		$this
			->setName('install')
			->setDescription('First install database and default backend theme')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->dialog = $this->getHelperSet()->get('dialog');
		$text="";

		if ($this->dialog->askConfirmation(
				$output,
				'<question>Are you sure to perform installation?</question> : ',
				false
			)) {
	
			if (SchemaCommand::updateSchema()) {
				$text .= '<info>Schema updated…</info>'.PHP_EOL;

				/*
				 * Create backend theme
				 */
				if (!$this->hasDefaultBackend()) {

					$theme = new Theme();
					$theme->setAvailable(true)
						  ->setBackendTheme(true)
						  ->setClassName("Themes\Rozier\RozierApp")
					;

					Kernel::getInstance()->em()->persist($theme);
					Kernel::getInstance()->em()->flush();

					$text .= '<info>Rozier back-end theme installed…</info>'.PHP_EOL;
				}
				else {
					$text .= '<error>A back-end theme is already installed.</error>'.PHP_EOL;
				}

				/*
				 * Create default translation
				 */
				if (!$this->hasDefaultTranslation()) {

					$defaultTrans = new Translation();
					$defaultTrans->setLocale("en_GB")
						  ->setName("Default translation")
					;

					Kernel::getInstance()->em()->persist($defaultTrans);
					Kernel::getInstance()->em()->flush();

					$text .= '<info>Default translation installed…</info>'.PHP_EOL;
				}
				else {
					$text .= '<error>A default translation is already installed.</error>'.PHP_EOL;
				}
			}
		}

		$output->writeln($text);
	}

	private function hasDefaultBackend()
	{
		$default = Kernel::getInstance()->em()
			->getRepository("RZ\Renzo\Core\Entities\Theme")
			->findOneBy(array("backendTheme"=>true));

		return $default !== null ? true : false;
	}

	public function hasDefaultTranslation()
	{
		$default = Kernel::getInstance()->em()
			->getRepository("RZ\Renzo\Core\Entities\Translation")
			->findOneBy(array());

		return $default !== null ? true : false;
	}
}