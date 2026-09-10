<?php

declare(strict_types=1);

namespace Modules\ERP\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

final class ERPPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'ERP';
    }

    public function getId(): string
    {
        return 'erp';
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Own the module's navigation group instead of having the panel list every module:
     * the group is created here, with this module's icon, when the plugin registers.
     */
    public function afterRegister(Panel $panel): void
    {
        $panel->navigationGroups([
            NavigationGroup::make()
                ->label('ERP')
                ->icon(Heroicon::OutlinedBuildingOffice),
        ]);
    }
}
