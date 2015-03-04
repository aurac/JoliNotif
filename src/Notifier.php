<?php

/**
 * This file is part of the JoliNotif project.
 *
 * (c) Loïck Piera <pyrech@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliNotif;

use JoliNotif\Driver\Driver;
use JoliNotif\Exception\InvalidNotificationException;
use JoliNotif\Exception\SystemNotSupportedException;
use JoliNotif\Util\PharExtractor;

class Notifier
{
    /**
     * @var Driver[]
     */
    private $drivers = [];

    /**
     * @var Driver
     */
    private $driverInUse;

    /**
     * @param Driver[] $drivers
     *
     * @throw SystemNotSupportedException if no drivers are supported
     */
    public function __construct(array $drivers)
    {
        $this->drivers = $drivers;

        $this->driverInUse = $this->chooseBestDriver();
    }

    /**
     * @return Driver
     */
    public function getDriverInUse()
    {
        return $this->driverInUse;
    }

    /**
     * @param Notification $notification
     *
     * @return bool
     */
    public function send(Notification $notification)
    {
        if (!$notification->getBody()) {
            throw new InvalidNotificationException($notification, 'Notification body can not be empty');
        }

        // This makes the icon accessible for native commands when it's embedded inside a phar
        if (($icon = $notification->getIcon()) && PharExtractor::isLocatedInsideAPhar($icon)) {
            $notification->setIcon(
                PharExtractor::extractFile($icon)
            );
        }

        return $this->driverInUse->send($notification);
    }

    /**
     * @return Driver
     *
     * @throw SystemNotSupportedException if no drivers are supported
     */
    private function chooseBestDriver()
    {
        $bestDriver = null;

        foreach ($this->drivers as $driver) {
            if (!$driver->isSupported()) {
                continue;
            }

            if (null !== $bestDriver && $bestDriver->getPriority() >= $driver->getPriority()) {
                continue;
            }

            $bestDriver = $driver;
        }

        if (null === $bestDriver) {
            throw new SystemNotSupportedException('No drivers support your system');
        }

        return $bestDriver;
    }
}
