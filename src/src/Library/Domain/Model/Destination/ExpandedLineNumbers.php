<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination;

use AlexApi\Plugin\System\Chococsv\Library\Domain\Util\CsvUtil;

final class ExpandedLineNumbers
{
    private readonly bool $isCurrentlyExpanded;
    private readonly array $expandedLineNumbers;

    private function __construct(array $expandedLineNumbers)
    {
        $this->isCurrentlyExpanded = ($expandedLineNumbers !== []);
        $this->expandedLineNumbers = $expandedLineNumbers;
    }

    public static function fromString(string $lineNumbersYouWant = ''): self
    {
        return (new self(CsvUtil::chooseLinesLikeAPrinter($lineNumbersYouWant)));
    }

    public function isExpanded(): bool
    {
        return $this->isCurrentlyExpanded;
    }

    public function asArray(): array
    {
        return $this->expandedLineNumbers;
    }

    public function asString(): string
    {
        return implode(',', $this->asArray());
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
