<?php

declare(strict_types=1);
/**
 *
 * @author Mr Alexandre J-S William ELISÉ <code@apiadept.com>
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license GPL-2.0-and-later GNU General Public License v2.0 or later
 * @link https://apiadept.com
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination;

use InvalidArgumentException;

final class ExtraDefaultFieldKeys
{
    private array $extraDefaultFieldKeys = [];

    private const ALLOWED = [
        'publish_up',
        'publish_down',
        'featured',
        'featured_up',
        'featured_down',
        'images',
        'urls',
    ];

    private function __construct(array $givenExtraDefaultFieldKeys)
    {
        $extraDefaultFieldKeys = array_filter($givenExtraDefaultFieldKeys);

        if (empty($extraDefaultFieldKeys)) {
            $this->extraDefaultFieldKeys = $extraDefaultFieldKeys;
            return;
        }

        if ((($extraDefaultFieldKeys !== []) && !array_intersect($extraDefaultFieldKeys, self::ALLOWED))) {
            throw new InvalidArgumentException('Invalid argument provided.', 422);
        }

        $this->extraDefaultFieldKeys = $extraDefaultFieldKeys;
    }

    public static function fromArray(array $value): self
    {
        return new self($value);
    }

    public function asString(): string
    {
        return implode(',', $this->asArray());
    }

    public function asArray(): array
    {
        return $this->extraDefaultFieldKeys;
    }

    public function __serialize(): array
    {
        return [];
    }

    public function __debugInfo(): ?array
    {
        return null;
    }
}
