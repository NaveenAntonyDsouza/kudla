<?php

/*
| Member exports are owner-only (owner's rule): every admin download that carries
| members' names, emails or messages needs export_member_data (privacy check,
| 4 Nov 2026: the payments export checked only that the person could open the
| revenue report, and the members table had a bulk "Export" with no check and
| no exporter).
*/

it('asks for the member-export permission on every export that carries member data', function () {
    $page = fn (string $name) => file_get_contents(app_path("Filament/Pages/{$name}.php"));
    $view = fn (string $name) => file_get_contents(resource_path("views/filament/pages/{$name}.blade.php"));

    foreach (['EngagementReports', 'RevenueReports'] as $name) {
        $code = $page($name);
        $method = substr($code, strpos($code, 'public function exportCsv()'), 600);
        expect($method)->toContain("abort_unless(\\App\\Support\\Permissions::can('export_member_data'), 403);");
    }
    foreach (['engagement-reports', 'revenue-reports'] as $name) {
        expect($view($name))->toContain("@if (\\App\\Support\\Permissions::can('export_member_data'))");
    }
    expect($page('MembersOverview'))->toContain("Permissions::can('export_member_data') ? CsvExportService::exportUsers() : abort(403)");
});

it('has no unguarded bulk export on the members table', function () {
    expect(file_get_contents(app_path('Filament/Resources/UserResource.php')))->not->toContain('ExportBulkAction::make(');
});
