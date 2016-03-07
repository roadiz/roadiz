<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file Recaptcha.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;

class Recaptcha extends Constraint
{
    /**
     * @var Request
     */
    public $request;

    /**
     * @var string
     */
    public $emptyMessage = 'you_must_show_youre_not_robot';

    /**
     * @var string
     */
    public $invalidMessage = 'recaptcha_is_invalid.try_again';

    /**
     * @var string
     */
    public $privateKey;
    /**
     * @var string
     */
    public $verifyUrl;

    /**
     * Construct
     *
     * @param Request $request
     * @param array $options
     */
    public function __construct(Request $request, array $options)
    {
        parent::__construct($options);
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getRequiredOptions()
    {
        return [
            'privateKey',
            'verifyUrl',
        ];
    }
}
