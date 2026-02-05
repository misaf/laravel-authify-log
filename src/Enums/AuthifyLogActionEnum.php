<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Enums;

enum AuthifyLogActionEnum: int
{
    case Attempting = 1;
    case Authenticated = 2;
    case CurrentDeviceLogout = 3;
    case Failed = 4;
    case Lockout = 5;
    case Login = 6;
    case Logout = 7;
    case OtherDeviceLogout = 8;
    case PasswordReset = 9;
    case PasswordResetLinkSent = 10;
    case Registered = 11;
    case Validated = 12;
    case Verified = 13;

    /**
     * @return array<int, int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Attempting            => __('authify-log::action-enum.attempting'),
            self::Authenticated         => __('authify-log::action-enum.authenticated'),
            self::CurrentDeviceLogout   => __('authify-log::action-enum.current_device_logout'),
            self::Failed                => __('authify-log::action-enum.failed'),
            self::Lockout               => __('authify-log::action-enum.lockout'),
            self::Login                 => __('authify-log::action-enum.login'),
            self::Logout                => __('authify-log::action-enum.logout'),
            self::OtherDeviceLogout     => __('authify-log::action-enum.other_device_logout'),
            self::PasswordReset         => __('authify-log::action-enum.password_reset'),
            self::PasswordResetLinkSent => __('authify-log::action-enum.password_reset_link_sent'),
            self::Registered            => __('authify-log::action-enum.registered'),
            self::Validated             => __('authify-log::action-enum.validated'),
            self::Verified              => __('authify-log::action-enum.verified'),
        };
    }
}
