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

final class BasePath implements StringAwareValueObjectInterface, ComparableValueObjectInterface
{
    private string $basePath = '';

    private function __construct(string $givenBasePath)
    {
        $basePath = trim($givenBasePath);

        if (!str_starts_with($basePath, '/')) {
            throw new InvalidArgumentException('Base path is invalid', 422);
        }

        $this->basePath = $basePath;
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
        return trim($this->basePath);
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
