<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CSVExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $invoices;
    protected $heading;

    public function __construct(array $invoices, array $heading)
    {
        $this->invoices = $invoices;
        $this->heading = $heading;
    }

    public function array(): array
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return $this->heading;
    }
}
