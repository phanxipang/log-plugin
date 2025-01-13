<?php

declare(strict_types=1);

namespace Fansipan\Log\Tests;

use Fansipan\Contracts\ConnectorInterface;
use Fansipan\Traits\ConnectorTrait;

final class Connector implements ConnectorInterface
{
    use ConnectorTrait;
}
