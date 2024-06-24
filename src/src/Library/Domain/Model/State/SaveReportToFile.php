<?php

declare(strict_types=1);
/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State;

use InvalidArgumentException;

final class SaveReportToFile
{
    private const ALLOWED = [0, 1, 2];

    private int $saveReportToFile = 0;


    private function __construct(int $saveReportToFile)
    {
        if (!in_array($saveReportToFile, self::ALLOWED, true)) {
            throw new InvalidArgumentException('Invalid argument provided', 422);
        }

        $this->saveReportToFile = $saveReportToFile;
    }

    public static function fromInt(int $value): self
    {
        return (new self($value));
    }

    public function asInt(): int
    {
        return $this->saveReportToFile;
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
