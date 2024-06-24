<?php

declare(strict_types=1);

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */


namespace AlexApi\Plugin\System\Chococsv\Library\Domain\Model\State;

use InvalidArgumentException;

final class DeployArticleCommandState
{
    public const ASCII_BANNER = <<<TEXT
    __  __     ____         _____                              __                      __
   / / / ___  / / ____     / ___/__  ______  ___  _____       / ____  ____  ____ ___  / ___  __________
  / /_/ / _ \/ / / __ \    \__ \/ / / / __ \/ _ \/ ___/  __  / / __ \/ __ \/ __ `__ \/ / _ \/ ___/ ___/
 / __  /  __/ / / /_/ /   ___/ / /_/ / /_/ /  __/ /     / /_/ / /_/ / /_/ / / / / / / /  __/ /  (__  )
/_/ /_/\___/_/_/\____/   /____/\__,_/ .___/\___/_/      \____/\____/\____/_/ /_/ /_/_/\___/_/  /____/
                                   /_/
TEXT;

    public const REQUEST_TIMEOUT = 10;

    public const DEFAULT_ARTICLE_KEYS = [
        'id',
        'tokenindex',
        'access',
        'title',
        'alias',
        'catid',
        'articletext',
        'introtext',
        'fulltext',
        'language',
        'metadesc',
        'metakey',
        'state',
    ];

    public const MAX_RETRIES = 3;

    /**
     * @var array $destinations
     */
    private array $destinations = [];
    private bool $showAsciiBanner = false;

    private bool $isDone = false;

    private function __construct(
        array $destinations,
        private SilentMode $silent,
        private SaveReportToFile $saveReportToFile
    ) {
        if (empty($destinations)) {
            throw new InvalidArgumentException(
                'Destinations subform MUST contain at least one destination where your articles will be deployed',
                422
            );
        }

        $this->destinations = $destinations;
    }

    public static function fromState(
        array $givenDestinations,
        int $givenSilent = 0,
        int $givenSaveReportToFile = 0
    ): static {
        return (new static(
            $givenDestinations,
            SilentMode::fromInt($givenSilent),
            SaveReportToFile::fromInt($givenSaveReportToFile)
        ));
    }

    public function withAsciiBanner(bool $showAsciiBanner = false): static
    {
        $cloned = clone $this;
        $cloned->showAsciiBanner = $showAsciiBanner;
        return $cloned;
    }

    public function shouldShowAsciiBanner(): bool
    {
        return $this->showAsciiBanner;
    }

    public function withDone(bool $value): static
    {
        $cloned = clone $this;
        $cloned->isDone = $value;
        return $cloned;
    }

    public function getDestinations(): array
    {
        return $this->destinations;
    }


    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function getSilent(): SilentMode
    {
        return $this->silent;
    }

    public function getSaveReportToFile(): SaveReportToFile
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
