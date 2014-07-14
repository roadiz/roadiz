<?php 
/**
 * Copyright REZO ZERO 2014
 * 
 * 
 * 
 *
 * @file MixedUrlMatcher.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Core\Routing;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Translation;
use RZ\Renzo\Core\Entities\Node;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;


class MixedUrlMatcher extends UrlMatcher
{

	/**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $this->allow = array();

        $decodedUrl = rawurldecode($pathinfo);

        /*
         * set default locale
         */
        $translation = Kernel::getInstance()->em()
        	->getRepository('RZ\Renzo\Core\Entities\Translation')
        	->findOneBy(array('defaultTranslation'=>true, 'available'=>true));

        if ($translation !== null) {
        	Kernel::getInstance()->getRequest()->setLocale($translation->getShortLocale());
        }

        /*
         * First try matching Static routes
         *
         * Backend and Frontend
         */
        if ($ret = $this->matchCollection($decodedUrl, $this->routes)) {
            return $ret;
        }
        /*
         * Then match Frontend node routes
         */
        elseif ($ret = $this->matchNode($decodedUrl)) {
        	return $ret;
        }

        throw 0 < count($this->allow)
            ? new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allow)))
            : new ResourceNotFoundException();
    }

    /**
     * 
     * @param  string $decodedUrl
     * @return array 
     */
    private function matchNode($decodedUrl)
    {
    	$tokens = explode('/', $decodedUrl);
    	$tokens = array_values(array_filter($tokens)); // Remove empty tokens (especially when a trailing slash is present)


    	/*
    	 * Try with URL Aliases
    	 *
    	 */
    	$node = $this->parseFromUrlAlias($tokens);
    	if ($node !== null) {

    		$translation = $node->getNodeSources()->first()->getTranslation();
			Kernel::getInstance()->getRequest()->setLocale($translation->getShortLocale());

    		return array(
	    		'_controller' => $this->getThemeController().'::indexAction',
	    		'node' => $node,
	    		'urlAlias' => null,
	    		'translation' => $translation
	    	);
    	}

    	/*
    	 * Try with node name
    	 */
    	$node = $this->parseNode($tokens);
    	if ( $node !== null ) {
	    	/*
	    	 * Try with nodeName
	    	 */
	    	$translation = $this->parseTranslation($tokens);
	    	Kernel::getInstance()->getRequest()->setLocale($translation->getShortLocale());

	    	return array(
	    		'_controller' => $this->getThemeController().'::indexAction',
	    		'node' => $this->parseNode($tokens),
	    		'urlAlias' => null,
	    		'translation' => $translation
	    	);
    	}

    	return false;
    }

    /**
     * Get Theme front controller class FQN
     * 
     * @return string Full qualified Classname
     */
    public function getThemeController()
    {
    	$theme = Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\Theme')
						->findOneBy(array(
							'available'=>true, 
							'backendTheme'=> false
						));

		if ($theme !== null) {
			return $theme->getClassName();
		}

    	return 'RZ\Renzo\CMS\Controllers\FrontendController';
    }

    /**
	 * Parse URL searching nodeName
	 * 
	 * @param  array $tokens
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function parseNode( &$tokens )
	{
		if (!empty($tokens[0])) {

			/*
			 * If the only url token if for language, return no url alias !
			 */
			if (in_array($tokens[0], Translation::getAvailableLocalesShortcuts()) && 
				count($tokens) == 1) 
			{
				return null;
			}
			else {
				$identifier = strip_tags($tokens[(int)(count($tokens) - 1)]);

				if ($identifier !== null && 
					$identifier != '') {

					return Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\Node')
						->findOneBy(array('nodeName'=>$identifier));
				}
			}
		}
		return null;
	}

	/**
	 * Parse URL searching UrlAlias 
	 * 
	 * @param  array $tokens [description]
	 * @return RZ\Renzo\Core\Entities\Node
	 */
	private function parseFromUrlAlias( &$tokens )
	{
		if (!empty($tokens[0])) {

			/*
			 * If the only url token if for language, return no url alias !
			 */
			if (in_array($tokens[0], Translation::getAvailableLocalesShortcuts()) && 
				count($tokens) == 1) 
			{
				return null;
			}
			else {
				$identifier = strip_tags($tokens[(int)(count($tokens) - 1)]);

				if ($identifier !== null && 
					$identifier != '') {

					$ua = Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
						->findOneBy(array('alias'=>$identifier));

					if ($ua !== null) {
						return Kernel::getInstance()->em()
							->getRepository('RZ\Renzo\Core\Entities\Node')
							->findOneWithUrlAlias($ua);
					}
				}
			}
		}
		return null;
	}

	/**
	 * Parse translation from URL tokens
	 * 
	 * @param  array $tokens
	 * @return RZ\Renzo\Core\Entities\Translation
	 */
	private function parseTranslation( &$tokens )
	{

		if (!empty($tokens[0])) 
		{
			$firstToken = $tokens[0];
			/*
			 * First token is for language
			 */
			if (in_array($firstToken, Translation::getAvailableLocales()) || 
				in_array($firstToken, Translation::getAvailableLocalesShortcuts())) 
			{
				$locale = null;

				if (in_array($firstToken, Translation::getAvailableLocalesShortcuts())) {
					$locale = Translation::getLocaleFromShortcut(strip_tags($firstToken));
				}
				else {
					$locale = strip_tags($firstToken);
				}

				if ($locale !== null && $locale != '') {
					return Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\Translation')
						->findOneBy(array('locale'=>$locale));
				}
			}
			else {
				return Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\Translation')
						->findOneBy(array('defaultTranslation'=>true));
			}
		}
		return null;
	}
}