<?php

namespace Soflyy\WpAllExportPro\Vendor\PhpOffice\PhpSpreadsheet\Writer;

use Soflyy\WpAllExportPro\Vendor\ZipStream\Option\Archive;
use Soflyy\WpAllExportPro\Vendor\ZipStream\ZipStream;

class ZipStream2
{
    /**
     * @param resource $fileHandle
     */
    public static function newZipStream($fileHandle): ZipStream
    {
        $options = new Archive();
        $options->setEnableZip64(false);
        $options->setOutputStream($fileHandle);

        return new ZipStream(null, $options);
    }
}
