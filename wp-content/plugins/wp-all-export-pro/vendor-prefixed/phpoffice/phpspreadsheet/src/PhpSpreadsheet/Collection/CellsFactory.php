<?php

namespace Soflyy\WpAllExportPro\Vendor\PhpOffice\PhpSpreadsheet\Collection;

use Soflyy\WpAllExportPro\Vendor\PhpOffice\PhpSpreadsheet\Settings;
use Soflyy\WpAllExportPro\Vendor\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class CellsFactory
{
    /**
     * Initialise the cache storage.
     *
     * @param Worksheet $worksheet Enable cell caching for this worksheet
     *
     * */
    public static function getInstance(Worksheet $worksheet): Cells
    {
        return new Cells($worksheet, Settings::getCache());
    }
}
