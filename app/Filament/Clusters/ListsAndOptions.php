<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Site & Content → Lists & Options: the dropdown lists members pick from
 * (Reference Data) and their options (Dropdown Options), until then two menu
 * entries for the same lists (admin audit, Oct 2026).
 */
class ListsAndOptions extends Cluster
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Lists & Options';
    protected static \UnitEnum|string|null $navigationGroup = 'Site & Content';
    protected static ?int $navigationSort = 11;
    protected static ?string $clusterBreadcrumb = 'Lists & Options';
    // tabs along the top: a side menu squeezed the tables
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = \Filament\Pages\Enums\SubNavigationPosition::Top;
}
