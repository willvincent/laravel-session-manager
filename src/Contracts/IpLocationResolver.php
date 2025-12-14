<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Contracts;

use WillVincent\SessionManager\Data\SessionLocation;

interface IpLocationResolver
{
    public function resolve(string $ip): ?SessionLocation;
}
