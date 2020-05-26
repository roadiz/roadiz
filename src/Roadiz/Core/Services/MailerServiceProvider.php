<?php
declare(strict_types=1);

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
        $container['mailer.transport'] = function (Container $c) {
            if ($c['config']['mailer']['type'] == "smtp") {
                $transport = new \Swift_SmtpTransport();

                if (!empty($c['config']['mailer']['host'])) {
                    $transport->setHost($c['config']['mailer']['host']);
                } else {
                    $transport->setHost('localhost');
                }
                $transport->setPort($c['config']['mailer']['port']);

                if (null !== $c['config']['mailer']['encryption'] && false !== $c['config']['mailer']['encryption']) {
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
                return new \Swift_SendmailTransport();
            }
        };

        $container['mailer'] = function (Container $c) {
            return new \Swift_Mailer($c['mailer.transport']);
        };

        return $container;
    }
}
