<?php 

namespace RZ\Renzo\Core\Viewers;


interface ViewableInterface {
	/**
	 * Get cache directory path for current viewable template engine
	 * @return string
	 */
	public function getCacheDirectory();

	/**
	 * Empty twig renderer cache if Kernel is in debug mode
	 * @return void
	 */
	public function handleTwigCache();

	/**
	 * Create a twig renderer engine instance
	 * @return void
	 */
	public function initializeTwig();

	/**
	 * Return current viewable twig engine instance
	 * @return \Twig_Environment
	 */
	public function getTwig();

	/**
	 * Create a translator instance and load theme messages
	 * 
	 * {{themeDir}}/Resources/translations/messages.{{lang}}.xlf
	 * 
	 * @todo  [Cache] Need to write XLF catalog to PHP using \Symfony\Component\Translation\Writer\TranslationWriter 
	 * @return  this
	 */
	public function initializeTranslator();
	/**
	 * @return Symfony\Component\Translation\Translator
	 */
	public function getTranslator();
}