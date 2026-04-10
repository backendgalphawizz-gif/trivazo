<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TowProviderListExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $data;
    protected $filters;
    protected $statistics;
    protected $search;

    public function __construct($data)
    {
        $this->data = $data['providers'] ?? collect();
        $this->filters = $data['filters'] ?? [];
        $this->statistics = $data['statistics'] ?? [];
        $this->search = $data['search'] ?? '';
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Company Name',
            'Owner Name',
            'Phone',
            'Email',
            'Service Area',
            'Status',
            'Rating',
            'Total Trips',
            'Current Trips',
            'Max Trips',
            'Joined Date',
            'Last Location Update'
        ];
    }

    public function map($provider): array
    {
        return [
            $provider->id,
            $provider->company_name,
            $provider->owner_name ?? 'N/A',
            $provider->owner_phone ?? 'N/A',
            $provider->owner_email ?? 'N/A',
            $provider->service_area ?? 'All Areas',
            ucfirst(str_replace('_', ' ', $provider->status)),
            number_format($provider->rating, 1) . ' ⭐',
            $provider->total_completed_trips,
            $provider->current_trips_count,
            $provider->max_simultaneous_trips,
            $provider->created_at?->format('Y-m-d H:i') ?? 'N/A',
            $provider->last_location_update?->format('Y-m-d H:i') ?? 'Never',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:M1')
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setARGB('FFFFFF');

                $event->sheet->getDelegate()->getStyle('A1:M1')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('2C3E50');

                $event->sheet->getDelegate()->getStyle('A1:M' . ($this->collection()->count() + 1))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $event->sheet->getDelegate()->getStyle('A1:M' . ($this->collection()->count() + 1))
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}