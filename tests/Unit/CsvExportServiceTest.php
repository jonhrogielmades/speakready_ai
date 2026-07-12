<?php

namespace Tests\Unit;

use App\Services\CsvExportService;
use PHPUnit\Framework\TestCase;

class CsvExportServiceTest extends TestCase
{
    public function test_it_neutralizes_spreadsheet_formulas(): void
    {
        foreach (['=SUM(1,1)', '+cmd', '-2+3', '@IMPORTXML("url")', "\tformula", "\rformula"] as $value) {
            $this->assertSame("'" . $value, CsvExportService::safeCell($value));
        }
    }

    public function test_it_preserves_normal_csv_values(): void
    {
        $this->assertSame('Candidate Name', CsvExportService::safeCell('Candidate Name'));
        $this->assertSame('42', CsvExportService::safeCell(42));
        $this->assertSame('', CsvExportService::safeCell(null));
    }
}
