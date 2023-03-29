<?php

declare(strict_types=1);

namespace App\Command;

use Certificationy\Cli\Command\StartCommand;

class TestCommand extends StartCommand
{
    protected static $defaultName = 'certificationy:test';

}
