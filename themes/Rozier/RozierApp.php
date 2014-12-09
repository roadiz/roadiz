<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
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
 * @file BackendController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier;

use RZ\Roadiz\CMS\Controllers\BackendController;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Bags\SettingsBag;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;
use Themes\Rozier\Widgets\FolderTreeWidget;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rozier main theme application
 */
class RozierApp extends BackendController
{
    protected static $themeName =      'Rozier administration theme';
    protected static $themeAuthor =    'Ambroise Maupate, Julien Blanchet';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir =       'Rozier';

    protected $formFactory = null;

    /**
     * @return array $assignation
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        if (!$this->getKernel()->getRequest()->isXmlHttpRequest()) {
            $this->assignation['nodeTree'] = new NodeTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['tagTree'] = new TagTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['folderTree'] = new FolderTreeWidget($this->getKernel()->getRequest(), $this);
        }

        //Settings
        $this->assignation['head']['siteTitle'] = SettingsBag::get('site_name').' back-office';
        $this->assignation['head']['mapsStyle'] = SettingsBag::get('maps_style');

        $this->assignation['head']['mainColor'] = SettingsBag::get('main_color');
        $this->assignation['head']['googleClientId'] = SettingsBag::get('google_client_id') ? SettingsBag::get('google_client_id') : "";

        $this->assignation['head']['grunt'] = include(dirname(__FILE__).'/static/public/config/assets.config.php');

        $this->assignation['settingGroups'] = $this->getService('em')
                                                   ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                                                   ->findBy(array('inMenu' => true), array('name'=>'ASC'));

        /*
         * Get admin image
         */
        $adminImage = $this->getService('em')
                           ->getRepository('RZ\Roadiz\Core\Entities\DocumentTranslation')
                           ->findOneBy(array(
                                'name' => '_admin_image_'
                            ));
        if (null !== $adminImage) {
            $this->assignation['adminImage'] = $adminImage->getDocument();
        }

        $this->assignation['nodeStatuses'] = array(
            'draft' => Node::DRAFT,
            'pending' => Node::PENDING,
            'published' => Node::PUBLISHED,
            'archived' => Node::ARCHIVED,
            'deleted' => Node::DELETED
        );

        return $this;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function indexAction(Request $request)
    {
        return new Response(
            $this->getTwig()->render('index.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function cssAction(Request $request)
    {

        $this->assignation['mainColor'] = SettingsBag::get('main_color');

        return new Response(
            $this->getTwig()->render('css/mainColor.css.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/css')
        );
    }
}
