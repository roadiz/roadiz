<?php
/*
 * Copyright REZO ZERO 2014
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

/**
 * Extends compiled UrlMatcher to add a dynamic routing feature which deals
 * with NodesSources URL.
 */
class MixedUrlMatcher extends \GlobalUrlMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        Kernel::getService('stopwatch')->start('matchingRoute');
        $decodedUrl = rawurldecode($pathinfo);

        try {
            /*
             * Try STATIC routes
             */
            return parent::match($pathinfo);

        } catch (ResourceNotFoundException $e) {
            /*
             * Try nodes routes
             */
            if (false !== $ret = $this->matchNode($decodedUrl)) {
                return $ret;
            } else {
                //throw new ResourceNotFoundException();
                return array(
                    '_controller' => $this->getThemeController().'::throw404',
                    'message' => 'Unable to find any matching route nor matching node.'.
                                 ' Check your `Resources/routes.yml` file.'
                );
            }
        }
    }

    /**
     * @param string $decodedUrl
     *
     * @return array
     */
    private function matchNode($decodedUrl)
    {
        $tokens = explode('/', $decodedUrl);
        // Remove empty tokens (especially when a trailing slash is present)
        $tokens = array_values(array_filter($tokens));

        /*
         * Try with URL Aliases
         */
        $node = $this->parseFromUrlAlias($tokens);
        if ($node !== null) {

            $translation = $node->getNodeSources()->first()->getTranslation();
            Kernel::getInstance()->getRequest()->setLocale($translation->getShortLocale());

            return array(
                '_controller' => $this->getThemeController().'::indexAction',
                'node'        => $node,
                'urlAlias'    => null,
                'translation' => $translation
            );
        } else {
            /*
             * Try with node name
             */
            $translation = $this->parseTranslation($tokens);

            if (null !== $translation) {
                Kernel::getInstance()->getRequest()->setLocale($translation->getShortLocale());
            }

            $node = $this->parseNode($tokens, $translation);
            if ($node !== null) {
                /*
                 * Try with nodeName
                 */
                return array(
                    '_controller' => $this->getThemeController().'::indexAction',
                    'node'        => $node,
                    'urlAlias'    => null,
                    'translation' => $translation
                );
            } else {
                return false;
            }
        }
    }

    /**
     * Get Theme front controller class FQN.
     *
     * @return string Full qualified Classname
     */
    public function getThemeController()
    {
        $host = $this->context->getHost();
        /*
         * First we look for theme according to hostname.
         */
        $theme = Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Theme')
                        ->findOneBy(array(
                            'available'=>    true,
                            'backendTheme'=> false,
                            'hostname'=>     $host
                        ));

        /*
         * If no theme for current host, we look for
         * any frontend available theme.
         */
        if (null === $theme) {
            $theme = Kernel::getService('em')
                            ->getRepository('RZ\Renzo\Core\Entities\Theme')
                            ->findOneBy(array(
                                'available'=>    true,
                                'backendTheme'=> false
                            ));
        }

        if (null !== $theme) {
            return $theme->getClassName();
        } else {
            return 'RZ\Renzo\CMS\Controllers\FrontendController';
        }
    }

    /**
     * Parse URL searching nodeName.
     *
     * @param array       &$tokens
     * @param Translation $translation
     *
     * @return RZ\Renzo\Core\Entities\Node
     */
    private function parseNode(&$tokens, Translation $translation)
    {
        if (!empty($tokens[0])) {

            /*
             * If the only url token is for language, return Home page
             */
            if (in_array($tokens[0], Translation::getAvailableLocalesShortcuts()) &&
                count($tokens) == 1) {
                return Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Node')
                        ->findHomeWithTranslation($translation);
            } else {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier !== null &&
                    $identifier != '') {

                    return Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Node')
                        ->findByNodeNameWithTranslation($identifier, $translation);
                }
            }
        }

        return null;
    }

    /**
     * Parse URL searching UrlAlias.
     *
     * @param array &$tokens [description]
     *
     * @return RZ\Renzo\Core\Entities\Node
     */
    private function parseFromUrlAlias(&$tokens)
    {
        if (!empty($tokens[0])) {

            /*
             * If the only url token if for language, return no url alias !
             */
            if (in_array($tokens[0], Translation::getAvailableLocalesShortcuts()) &&
                count($tokens) == 1) {
                return null;
            } else {
                $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);

                if ($identifier != '') {

                    $ua = Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\UrlAlias')
                        ->findOneBy(array('alias'=>$identifier));

                    if ($ua !== null) {
                        return Kernel::getService('em')
                            ->getRepository('RZ\Renzo\Core\Entities\Node')
                            ->findOneWithUrlAlias($ua);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse translation from URL tokens.
     *
     * @param array &$tokens
     *
     * @return RZ\Renzo\Core\Entities\Translation
     */
    private function parseTranslation(&$tokens)
    {
        if (!empty($tokens[0])) {

            $firstToken = $tokens[0];
            /*
             * First token is for language
             */
            if (in_array($firstToken, Translation::getAvailableLocales()) ||
                in_array($firstToken, Translation::getAvailableLocalesShortcuts())) {
                $locale = null;

                if (in_array($firstToken, Translation::getAvailableLocalesShortcuts())) {
                    $locale = Translation::getLocaleFromShortcut(strip_tags($firstToken));
                } else {
                    $locale = strip_tags($firstToken);
                }

                if ($locale !== null && $locale != '') {
                    return Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                        ->findOneBy(array('locale'=>$locale));
                }
            } else {
                return Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                        ->findOneBy(array('defaultTranslation'=>true));
            }
        }

        return Kernel::getService('em')
                        ->getRepository('RZ\Renzo\Core\Entities\Translation')
                        ->findDefault();
    }
}
