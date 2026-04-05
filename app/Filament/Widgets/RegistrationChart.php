<?php

namespace App\Filament\Widgets;

use App\Models\Profile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RegistrationChart extends ChartWidget
{
    protected ?string $heading = 'Registrations (Last 30 Days)';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d M');
            $data[] = Profile::whereDate('created_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Registrations',
                    'data' => $data,
                    'borderColor' => '#8B1D91',
                    'backgroundColor' => 'rgba(139, 29, 145, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
