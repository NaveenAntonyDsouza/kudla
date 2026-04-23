<x-filament-panels::page>
    <form wire:submit="upload">
        {{ $this->form }}

        <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
            <x-filament::button type="submit" color="primary">
                Upload &amp; Validate
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="\App\Filament\Resources\BulkImportResource::getUrl('index')"
                color="gray">
                Cancel
            </x-filament::button>
        </div>
    </form>

    <div style="margin-top: 2rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
        <h3 style="font-weight: 600; color: #111827; margin-bottom: 0.5rem;">Before you upload:</h3>
        <ol style="list-style-position: inside; color: #374151; font-size: 0.875rem; line-height: 1.7;">
            <li>Download the <strong>CSV Template</strong> (button on the list page) and fill it with your data.</li>
            <li>Download the <strong>Reference Data</strong> CSV to see valid values for columns like mother_tongue and height.</li>
            <li>Save your file as CSV (UTF-8). Max 1000 rows, 5 MB.</li>
            <li>Upload here — we'll validate EVERY row and show you a preview before anything is imported.</li>
            <li>Review the preview, fix any errors in your source file, and re-upload if needed.</li>
        </ol>
    </div>
</x-filament-panels::page>
