<?php

declare(strict_types=1);
/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination;

use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Common\ComparableValueObjectInterface;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Common\StringAwareValueObjectInterface;
use InvalidArgumentException;

use function str_starts_with;

final class BaseUrl implements StringAwareValueObjectInterface, ComparableValueObjectInterface
{
    private string $baseUrl = '';

    private function __construct(string $baseUrl)
    {
        if (!(str_starts_with($baseUrl, 'http://')
            || str_starts_with($baseUrl, 'https://')
        )) {
            throw new InvalidArgumentException('Base Url is invalid', 422);
        }

        $this->baseUrl = $baseUrl;
    }

    public static function fromString(string $value): static
    {
        return (new self($value));
    }

    public static function getRegex(): string
    {
        return '';
    }

    public function asString(): string
    {
        return $this->baseUrl;
    }

    public function equals(StringAwareValueObjectInterface $other): bool
    {
        return $this->asString() === $other->asString();
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
