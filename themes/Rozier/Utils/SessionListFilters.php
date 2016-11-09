<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file SessionListFilters.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace Themes\Rozier\Utils;

use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SessionListFilters.
 *
 * Store user item_per_page preferences in session.
 *
 * @package Themes\Rozier\Utils
 */
class SessionListFilters
{
    /**
     * @var string
     */
    private $sessionIdentifier;

    /**
     * SessionListFilters constructor.
     * @param string $sessionIdentifier
     */
    public function __construct($sessionIdentifier)
    {
        $this->sessionIdentifier = $sessionIdentifier;
    }

    /**
     * Handle item_per_page filter form session or from request query
     *
     * @param Request $request
     * @param EntityListManager $listManager
     */
    public function handleItemPerPage(Request $request, EntityListManager $listManager)
    {
        /*
         * Check if item_per_page is available from session
         */
        if (null !== $request->getSession() &&
            $request->getSession()->has($this->sessionIdentifier) &&
            $request->getSession()->get($this->sessionIdentifier) > 0 &&
            (!$request->query->has('item_per_page') ||
                $request->query->get('item_per_page') < 1)) {
            /*
             * Item count is in session
             */
            $request->query->set('item_per_page', $request->getSession()->get($this->sessionIdentifier));
            $listManager->setItemPerPage($request->getSession()->get($this->sessionIdentifier));
        } elseif ($request->query->has('item_per_page') &&
            $request->query->get('item_per_page') > 0) {
            /*
             * Item count is in query, save it in session
             */
            $request->getSession()->set($this->sessionIdentifier, $request->query->get('item_per_page'));
            $listManager->setItemPerPage($request->query->get('item_per_page'));
        }
    }
}
