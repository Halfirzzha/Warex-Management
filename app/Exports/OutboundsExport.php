<?php

namespace App\Exports;

use App\Models\Outbound;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutboundsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Outbound::query()->with(['creator', 'approver', 'inbound']);

        if (isset($this->filters['warehouse'])) {
            $query->where('warehouse_from', $this->filters['warehouse']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['purpose'])) {
            $query->where('purpose_type', $this->filters['purpose']);
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
            'Warehouse From',
            'Purpose',
            'Department',
            'PIC Name',
            'Quantity',
            'Unit',
            'Total Containers',
            'Total Weight (kg)',
            'Status',
            'Inbound Code',
            'Created By',
            'Approved By',
            'Created At',
            'Handed At',
        ];
    }

    public function map($outbound): array
    {
        return [
            $outbound->code,
            $outbound->name_product,
            ucfirst(str_replace('_', ' ', $outbound->warehouse_from)),
            ucfirst(str_replace('_', ' ', $outbound->purpose_type)),
            $outbound->department ?? '-',
            $outbound->pic_name ?? '-',
            $outbound->quantity,
            $outbound->unit_type,
            $outbound->total_containers,
            $outbound->total_weight_kg,
            strtoupper($outbound->status),
            $outbound->inbound?->code ?? '-',
            $outbound->creator?->name ?? 'System',
            $outbound->approver?->name ?? '-',
            $outbound->created_at->format('Y-m-d H:i:s'),
            $outbound->handed_at?->format('Y-m-d H:i:s') ?? '-',
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
        return 'Outbound Transactions';
    }
}
