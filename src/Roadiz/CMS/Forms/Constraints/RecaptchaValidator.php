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
 * @file RecaptchaValidator.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @see https://github.com/thrace-project/form-bundle/blob/master/Validator/Constraint/RecaptchaValidator.php
 */
class RecaptchaValidator extends ConstraintValidator
{
    /**
     *
     * @see \Symfony\Component\Validator\ConstraintValidator::validate()
     * @param mixed $data
     * @param Constraint $constraint
     */
    public function validate($data, Constraint $constraint)
    {
        $propertyPath = $this->context->getPropertyPath();
        $responseField = $constraint->request->request->get('g-recaptcha-response');

        if (empty($responseField)) {
            $this->context->buildViolation($constraint->emptyMessage)
                ->atPath($propertyPath)
                ->addViolation();
        } elseif (false === $this->check($constraint, $responseField)) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->atPath($propertyPath)
                ->addViolation();
        }
    }

    /**
     * Makes a request to recaptcha service and checks if recaptcha field is valid.
     *
     * @param Constraint $constraint
     * @param string $responseField
     *
     * @return bool
     */
    protected function check(Constraint $constraint, $responseField)
    {
        $server = $constraint->request->server;

        $data = array(
            'secret' => $constraint->privateKey,
            'remoteip' => $server->get('REMOTE_ADDR'),
            'response' => $responseField,
        );

        $curl = curl_init($constraint->verifyUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_USERAGENT, 'reCAPTCHA/PHP');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);
        $response = explode("\r\n\r\n", $response, 2);

        return (isset($response[1]) && preg_match('/true/', $response[1]));
    }
}
