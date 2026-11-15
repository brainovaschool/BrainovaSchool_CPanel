<?php

namespace App\Imports;

use App\Repositories\Portal\SocialRepository;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Bulk "Suggest a reel" sheet from the Reels page's Bulk Import button -
 *  each row becomes one reel via SocialRepository::importReel(), same as
 *  typing it into the Add Reel form one at a time. Never aborts the whole
 *  file over one bad row - every row's outcome (added, skipped, or added
 *  with a warning) is collected here instead, and shown back to whoever
 *  uploaded it. */
class ReelsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $added = [];
    public array $skipped = [];
    public array $warnings = [];

    public function __construct(private int $userId, private bool $canAssign)
    {
    }

    public function collection(Collection $rows)
    {
        $repo = app(SocialRepository::class);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // row 1 is the heading row
            $result = $repo->importReel($row->toArray(), $this->userId, $this->canAssign);

            if (!$result['status']) {
                $this->skipped[] = "Row {$rowNum}: {$result['message']}";
                continue;
            }

            $this->added[] = $rowNum;
            foreach ($result['data']['warnings'] ?? [] as $w) {
                $this->warnings[] = "Row {$rowNum}: {$w}";
            }
        }
    }
}
