<?php

namespace App\Exports;

use App\Models\Stock;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StocksExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Stock::query()->with('inbound');

        if (isset($this->filters['warehouse'])) {
            $query->where('warehouse', $this->filters['warehouse']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('warehouse')->orderBy('name_product');
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Warehouse',
            'Initial Stock',
            'Available Stock',
            'Stock Out',
            'Reserved Stock',
            'Unit Type',
            'Container Type',
            'Status',
            'Stock Percentage',
            'Inbound Code',
            'Last Updated',
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->name_product,
            ucfirst(str_replace('_', ' ', $stock->warehouse)),
            $stock->initial_stock,
            $stock->available_stock,
            $stock->stock_out,
            $stock->reserved_stock,
            $stock->unit_type,
            $stock->container_type,
            strtoupper(str_replace('_', ' ', $stock->status)),
            $stock->stock_percentage . '%',
            $stock->inbound?->code ?? '-',
            $stock->updated_at->format('Y-m-d H:i:s'),
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
        return 'Stock Summary';
    }
}
