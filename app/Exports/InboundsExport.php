<?php

namespace App\Exports;

use App\Models\Inbound;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InboundsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Inbound::query()->with(['creator', 'approver']);

        if (isset($this->filters['warehouse'])) {
            $query->where('warehouse', $this->filters['warehouse']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Transaction Code',
            'Product Name',
            'Warehouse',
            'Quantity',
            'Unit',
            'Total Containers',
            'Container Type',
            'Total Weight (kg)',
            'Status',
            'Created By',
            'Approved By',
            'Created At',
            'Approved At',
        ];
    }

    public function map($inbound): array
    {
        return [
            $inbound->code,
            $inbound->name_product,
            ucfirst(str_replace('_', ' ', $inbound->warehouse)),
            $inbound->quantity,
            $inbound->unit_type,
            $inbound->total_containers,
            $inbound->container_type,
            $inbound->total_weight_kg,
            strtoupper($inbound->status),
            $inbound->creator?->name ?? 'System',
            $inbound->approver?->name ?? '-',
            $inbound->created_at->format('Y-m-d H:i:s'),
            $inbound->approved_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }

    public function title(): string
    {
        return 'Inbound Transactions';
    }
}
