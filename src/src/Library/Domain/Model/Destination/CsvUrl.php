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

use function filter_var;
use function str_starts_with;

use const FILTER_VALIDATE_URL;

final class CsvUrl implements StringAwareValueObjectInterface, ComparableValueObjectInterface
{
    private readonly string $csvUrl;

    private function __construct(string $csvUrl)
    {
        if (!((str_starts_with($csvUrl, 'file://') or str_starts_with($csvUrl, '/'))
            xor filter_var($csvUrl, FILTER_VALIDATE_URL))
        ) {
            throw new InvalidArgumentException('CSV Url is invalid', 422);
        }
        $this->csvUrl = $csvUrl;
    }

    public static function fromString(string $value): static
    {
        return new self($value);
    }

    public static function getRegex(): string
    {
        return '';
    }

    public function asString(): string
    {
        return $this->csvUrl;
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
