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
         * First try matching Backend routes
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
    	 *
    	 */
    	return array(
    		'_controller' => $this->getThemeController().'::indexAction',
    		'node' => $this->parseNode($tokens),
    		'urlAlias' => null,
    		'translation' => $this->parseTranslation($tokens)
    	);
    }

    /**
     * 
     * @return string
     */
    public function getThemeController()
    {
    	return 'RZ\Renzo\CMS\Controllers\FrontendController';
    }

    /**
	 * [parseNodeIdentifier description]
	 * @param  array $tokens [description]
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

				if ($identifier !== null && $identifier != '') {
					return Kernel::getInstance()->em()
						->getRepository('RZ\Renzo\Core\Entities\Node')
						->findOneBy(array('nodeName'=>$identifier));
				}
			}
		}
		
		return null;
	}

	/**
	 * 
	 * @param  array $tokens [description]
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
		}
		return null;
	}
}