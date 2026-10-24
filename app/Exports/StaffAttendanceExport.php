<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffAttendanceExport implements FromCollection, WithHeadings
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['Staff', 'Role', 'Date', 'Status', 'First Login'];
    }

    public function collection()
    {
        return collect($this->rows)->map(fn ($row) => [
            $row['staff'],
            $row['role'],
            $row['date'],
            $row['status'],
            $row['first_login'],
        ]);
    }
}
