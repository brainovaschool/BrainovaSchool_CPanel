<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentAttendanceExport implements FromCollection, WithHeadings
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return ['Student', 'Roll', 'Admission No', 'Class', 'Section', 'Subject', 'Date', 'Status', 'Note'];
    }

    public function collection()
    {
        return collect($this->rows)->map(fn ($row) => [
            $row['student'],
            $row['roll'],
            $row['admission_no'],
            $row['class'],
            $row['section'],
            $row['subject'],
            $row['date'],
            $row['status'],
            $row['note'],
        ]);
    }
}
