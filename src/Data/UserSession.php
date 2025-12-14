<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Data;

final readonly class UserSession
{
    public function __construct(
        public mixed $id,
        public mixed $ip_address,
        public mixed $user_agent,
        public mixed $last_activity,
        public bool $is_current,
        public SessionDevice $device,
        public ?SessionLocation $location = null,
    ) {}
}
