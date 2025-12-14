<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Data;

final readonly class SessionDevice
{
    public function __construct(
        public string|bool $browser,
        public float|string|false|null $browser_version,
        public string|bool $platform,
        public float|string|false|null $platform_version,
        public string|bool $device,
        public bool $is_desktop,
        public bool $is_mobile,
        public bool $is_tablet,
        public bool $is_robot,
    ) {}
}
