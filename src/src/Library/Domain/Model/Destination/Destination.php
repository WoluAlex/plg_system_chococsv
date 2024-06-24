<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination;

use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Common\ComparableValueObjectInterface;
use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Common\StringAwareValueObjectInterface;

final class Destination implements ComparableValueObjectInterface
{
    private CsvUrl|null $csvUrl = null;

    private ExtraDefaultFieldKeys|null $extraDefaultFieldKeys = null;

    private CustomFieldFieldKeys|null $customFieldKeys = null;

    private ExpandedLineNumbers|null $expandedLineNumbers = null;

    private Token|null $token = null;

    private BaseUrl|null $baseUrl = null;

    private BasePath|null $basePath = null;


    private function __construct(private TokenIndex $tokenIndex)
    {
    }

    public static function fromTokenIndex(TokenIndex $value): self
    {
        return (new self($value));
    }

    public function withTokenIndex(string $value): self
    {
        $cloned = clone $this;
        $cloned->tokenIndex = TokenIndex::fromString($value);
        return $cloned;
    }

    public function withCsvUrl(string $value): self
    {
        $cloned = clone $this;
        $cloned->csvUrl = CsvUrl::fromString($value);
        return $cloned;
    }

    public function withBaseUrl(string $value): self
    {
        $cloned = clone $this;
        $cloned->baseUrl = BaseUrl::fromString($value);
        return $cloned;
    }

    public function withBasePath(string $value): self
    {
        $cloned = clone $this;
        $cloned->basePath = BasePath::fromString($value);
        return $cloned;
    }

    public function withToken(string $value): self
    {
        $cloned = clone $this;
        $cloned->token = Token::fromString($value);
        return $cloned;
    }

    public function withExtraDefaultFieldKeys(array $value): self
    {
        $cloned = clone $this;
        $cloned->extraDefaultFieldKeys = ExtraDefaultFieldKeys::fromArray($value);
        return $cloned;
    }

    public function withCustomFieldKeys(array $value): self
    {
        $cloned = clone $this;
        $cloned->customFieldKeys = CustomFieldFieldKeys::fromArray($value);
        return $cloned;
    }

    public function withExpandedLineNumbers(string $value): self
    {
        $cloned = clone $this;
        $cloned->expandedLineNumbers = ExpandedLineNumbers::fromString($value);
        return $cloned;
    }

    public function getCsvUrl(): ?CsvUrl
    {
        return $this->csvUrl;
    }

    public function getExtraDefaultFieldKeys(): ?ExtraDefaultFieldKeys
    {
        return $this->extraDefaultFieldKeys;
    }

    public function getCustomFieldKeys(): ?CustomFieldFieldKeys
    {
        return $this->customFieldKeys;
    }

    public function getExpandedLineNumbers(): ?ExpandedLineNumbers
    {
        return $this->expandedLineNumbers;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function getBaseUrl(): ?BaseUrl
    {
        return $this->baseUrl;
    }

    public function getBasePath(): ?BasePath
    {
        return $this->basePath;
    }

    public function getTokenIndex(): TokenIndex
    {
        return $this->tokenIndex;
    }

    public function asString(): string
    {
        return $this->getTokenIndex()->asString();
    }

    public function equals(StringAwareValueObjectInterface $other): bool
    {
        return $this->asString() === $other->asString();
    }

    /**
     * Detect remote destination more or less reliably. Easy and quick check
     * @return bool
     */
    public function isRemote(): bool
    {
        return (
            (($this->getBaseUrl()?->asString() ?? '') !== '')
            && (($this->getBasePath()?->asString() ?? '') !== '')
            && (($this->getToken()?->asString() ?? '') !== '')
        );
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
