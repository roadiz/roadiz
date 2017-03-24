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
 * @file UserEvents.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\Events;

/**
 *
 */
final class UserEvents
{
    /**
     * Event user.created is triggered each time a user
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     */
    const USER_CREATED = 'user.created';

    /**
     * Event user.updated is triggered each time a user
     * is updated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     */
    const USER_UPDATED = 'user.updated';

    /**
     * Event user.deleted is triggered each time a user
     * is deleted.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     */
    const USER_DELETED = 'user.deleted';

    /**
     * Event user.enabled is triggered each time a user
     * is enabled.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     */
    const USER_ENABLED = 'user.enabled';

    /**
     * Event user.disabled is triggered each time a user
     * is disabled.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterUserEvent instance
     *
     * @var string
     */
    const USER_DISABLED = 'user.disabled';
}
