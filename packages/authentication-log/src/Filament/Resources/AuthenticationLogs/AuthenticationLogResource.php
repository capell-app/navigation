<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs;

use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\Tables\AuthenticationLogsTable;
use Filament\Tables\Table;
use Override;

class AuthenticationLogResource extends \Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource
{
    use HasTableConfigurator;

    protected static string $tableConfigurator = AuthenticationLogsTable::class;

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.authentication_logs');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_monitoring');
    }
}
