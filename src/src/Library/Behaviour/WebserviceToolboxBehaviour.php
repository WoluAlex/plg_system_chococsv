<?php

declare(strict_types=1);
/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Behaviour;

use Joomla\Http\HttpFactory;
use Joomla\Http\TransportInterface;

defined('_JEXEC') || die;

/**
 * A "toolbox" "enable" the caller to use Http Client and other Web Services related utilities
 */
trait WebserviceToolboxBehaviour
{
    /**
     * Get Transport Interface instance to act as Http Client
     *
     * @return TransportInterface
     */
    public static function getHttpClient(): TransportInterface
    {
        return (new HttpFactory())->getAvailableDriver(['Curl', 'Stream'], 'Curl');
    }

    public function __debugInfo(): ?array
    {
        return null;
    }

    public function __serialize(): array
    {
        return [];
    }
}
