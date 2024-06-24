<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */


namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Common;

use InvalidArgumentException;

abstract class AbstractValueObject implements StringAwareValueObjectInterface, ComparableValueObjectInterface
{
    protected string $value;
    protected const REGEX = '([\p{C}\p{L}\p{M}\p{N}\p{P}\p{S}\p{Z}]{1,65535})';

    private function __construct(string $givenValue)
    {
        $value = trim($givenValue);
        if (preg_match('/^' . static::REGEX . '$/Uu', $value) !== 1) {
            throw new InvalidArgumentException(
                message: ($value ? sprintf(
                    'Invalid argument provided %s. Cannot continue.',
                    $value
                ) : 'Empty value provided. Cannot continue'),
                code: 422
            );
        }
        $this->value = $value;
    }

    public static function fromString(string $value): static
    {
        return (new static($value));
    }

    public static function getRegex(): string
    {
        return static::REGEX;
    }

    public function asString(): string
    {
        return $this->value;
    }

    public function equals(StringAwareValueObjectInterface $other): bool
    {
        return $this->asString() === $other->asString();
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
