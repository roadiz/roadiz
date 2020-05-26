<?php
declare(strict_types=1);

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
        if ($request->hasPreviousSession() &&
            null !== $request->getSession() &&
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
