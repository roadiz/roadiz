<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file AuthCollector.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\DebugBar\DataCollector;


use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthCollector extends DataCollector implements Renderable
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @{inheritDoc}
     */
    public function collect()
    {
        if (null !== $this->tokenStorage->getToken() &&
            $this->tokenStorage->getToken()->getUser() instanceof UserInterface) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            return [
                'name' => $this->tokenStorage->getToken()->getUsername(),
                'user' => [
                    'Roles' => $user->getRoles(),
                    'Email' => $user->getEmail(),
                    'Last login' => $user->getLastLogin()->format("Y-m-d H:i:s"),
                ]
            ];
        }

        return [
            'name' => 'Guest',
            'user' => [],
        ];
    }

    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'auth';
    }

    /**
     * @{inheritDoc}
     */
    public function getWidgets()
    {
        $widgets = [
            'auth' => [
                'icon' => 'lock',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'auth.user',
                'default' => '{}'
            ]
        ];
        $widgets['auth.name'] = [
            'icon' => 'user',
            'tooltip' => 'Auth status',
            'map' => 'auth.name',
            'default' => '',
        ];

        return $widgets;
    }
}
