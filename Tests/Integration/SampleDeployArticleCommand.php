<?php

declare(strict_types=1);
namespace Tests\Integration;

use AlexApi\Plugin\System\Chococsv\Library\Command\AbstractDeployArticleCommand;

final class SampleDeployArticleCommand extends AbstractDeployArticleCommand
{
    protected function isSupported(): bool
    {
        return true;
    }
}
