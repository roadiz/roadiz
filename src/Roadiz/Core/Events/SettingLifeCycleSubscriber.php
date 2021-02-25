<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\HiddenString\HiddenString;
use Pimple\Container;
use RZ\Crypto\Encoder\UniqueKeyEncoderInterface;
use RZ\Roadiz\Core\Entities\Setting;

class SettingLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
            Events::postLoad
        ];
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $setting = $event->getEntity();
        if ($setting instanceof Setting) {
            if ($event->hasChangedField('encrypted') &&
                $event->getNewValue('encrypted') === false &&
                null !== $setting->getRawValue()) {
                /*
                 * Set raw value and do not encode it if setting is not encrypted no more.
                 */
                $this->container['logger.doctrine']->info(sprintf('Disabled encryption for %s setting.', $setting->getName()));
                $setting->setValue($setting->getRawValue());
            } elseif ($event->hasChangedField('encrypted') &&
                $event->getNewValue('encrypted') === true &&
                null !== $setting->getRawValue() &&
                null !== $this->getEncoder()) {
                /*
                 * Encode value for the first time.
                 */
                $this->container['logger.doctrine']->info(sprintf('Encode %s value for the first time.', $setting->getName()));
                $setting->setValue($this->getEncoder()->encode(new HiddenString($setting->getRawValue())));
            } elseif ($setting->isEncrypted() &&
                $event->hasChangedField('value') &&
                null !== $event->getNewValue('value') &&
                null !== $this->getEncoder()
            ) {
                /*
                 * Encode setting if value has changed
                 */
                $this->container['logger.doctrine']->info(sprintf('Encode %s value.', $setting->getName()));
                $event->setNewValue('value', $this->getEncoder()->encode(new HiddenString($event->getNewValue('value'))));
                $setting->setClearValue($event->getNewValue('value'));
            }
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $setting = $event->getEntity();
        if ($setting instanceof Setting &&
            $setting->isEncrypted() &&
            null !== $setting->getRawValue() &&
            null !== $this->getEncoder()
        ) {
            try {
                $this->container['logger.doctrine']->debug(sprintf('Decode %s value', $setting->getName()));
                $setting->setClearValue($this->getEncoder()->decode($setting->getRawValue())->getString());
            } catch (InvalidKey $exception) {
                $this->container['logger.doctrine']->debug(sprintf('Failed to decode %s value', $setting->getName()));
            } catch (InvalidMessage $exception) {
                $this->container['logger.doctrine']->debug(sprintf('Failed to decode %s value', $setting->getName()));
            }
        }
    }

    protected function getEncoder(): ?UniqueKeyEncoderInterface
    {
        return $this->container[UniqueKeyEncoderInterface::class];
    }
}
