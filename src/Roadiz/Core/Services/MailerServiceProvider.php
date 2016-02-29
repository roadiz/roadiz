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
 * @file MailerServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Register Mailer transport instance.
 */
class MailerServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize Mailer objects.
     *
     * To use a SMTP mailer, enter your server config in
     * `conf/config.json` file, for example:
     *
     *     - mailer
     *         - type: "smtp"
     *         - host: "smtp.example.org"
     *         - port: 587
     *         - encryption: "ssl"
     *         - username: "username"
     *         - password: "password"
     *
     * Just set `type` to false or remove `mailer` section
     * to enable simple `sendmail` transport.
     *
     * @param Container $container
     * @return Container
     */
    public function register(Container $container)
    {
        $container['mailer.transport'] = function ($c) {

            if (isset($c['config']['mailer']) &&
                isset($c['config']['mailer']['type']) &&
                strtolower($c['config']['mailer']['type']) == "smtp") {
                $transport = \Swift_SmtpTransport::newInstance();

                if (!empty($c['config']['mailer']['host'])) {
                    $transport->setHost($c['config']['mailer']['host']);
                } else {
                    $transport->setHost('localhost');
                }

                if (!empty($c['config']['mailer']['port'])) {
                    $transport->setPort((int) $c['config']['mailer']['port']);
                } else {
                    $transport->setPort(25);
                }

                if (!empty($c['config']['mailer']['encryption']) &&
                    (strtolower($c['config']['mailer']['encryption']) == "tls" ||
                        strtolower($c['config']['mailer']['encryption']) == "ssl")) {
                    $transport->setEncryption($c['config']['mailer']['encryption']);
                }

                if (!empty($c['config']['mailer']['username'])) {
                    $transport->setUsername($c['config']['mailer']['username']);
                }

                if (!empty($c['config']['mailer']['password'])) {
                    $transport->setPassword($c['config']['mailer']['password']);
                }

                return $transport;
            } else {
                return \Swift_MailTransport::newInstance();
            }
        };

        $container['mailer'] = function ($c) {
            return \Swift_Mailer::newInstance($c['mailer.transport']);
        };

        return $container;
    }
}
