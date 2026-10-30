<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

/**
 * Admin & Security → Audit Trail: who signed in (Login History) and what was
 * done (Activity Log), one menu entry with a tab for each (admin audit, Oct 2026).
 */
class AuditTrail extends Cluster
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Audit Trail';
    protected static \UnitEnum|string|null $navigationGroup = 'Admin & Security';
    protected static ?int $navigationSort = 4;
    protected static ?string $clusterBreadcrumb = 'Audit Trail';
    // tabs along the top: a side menu squeezed the tables
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = \Filament\Pages\Enums\SubNavigationPosition::Top;
}
