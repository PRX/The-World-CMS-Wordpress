<?php

namespace Wpae\Csv;


class CsvWriter
{
    const CSV_STRATEGY_DEFAULT = 'default';
    const CSV_STRATEGY_CUSTOM = 'custom';

    const ESCAPE_RFC = 'rfc';
    const ESCAPE_BACKSLASH = 'backslash';

    private $csvStrategy;

    public function __construct($csvStrategy = self::CSV_STRATEGY_DEFAULT)
    {
        $this->csvStrategy = $csvStrategy;
    }

    /**
     * RFC 4180 for XLS/XLSX (their CSV never leaves the server) and the custom strategy.
     * A plain CSV export follows its csv_escape option; exports saved before the option
     * existed have no key and keep PHP's backslash so their files keep their bytes.
     */
    public static function escapeFor($exportOptions)
    {
        $isSpreadsheet = isset($exportOptions['export_to'], $exportOptions['export_to_sheet'])
            && $exportOptions['export_to'] === 'csv'
            && in_array($exportOptions['export_to_sheet'], array('xls', 'xlsx'), true);

        if ($isSpreadsheet || apply_filters('wp_all_export_csv_strategy', self::CSV_STRATEGY_DEFAULT) === self::CSV_STRATEGY_CUSTOM) {
            return '';
        }

        return self::formatFor($exportOptions) === self::ESCAPE_RFC ? '' : '\\';
    }

    public static function formatFor($exportOptions)
    {
        return isset($exportOptions['csv_escape']) && $exportOptions['csv_escape'] === self::ESCAPE_RFC ? self::ESCAPE_RFC : self::ESCAPE_BACKSLASH;
    }

    public static function markerFor($exportOptions)
    {
        return self::escapeFor($exportOptions) === '' ? self::ESCAPE_RFC : self::ESCAPE_BACKSLASH;
    }

    public function writeCsv($resource, $value, $delimiter)
    {
        $value = apply_filters('pmxe_csv_value', $value);

        foreach($value as $key => &$val ) {
            if(is_object($val)) {
                $val = '';
            }
        }

        $this->writeRow($resource, $value, $delimiter);
    }

    /**
     * Writes an already-filtered row, for rewrites of a file this writer produced.
     */
    public function writeRow($resource, $value, $delimiter)
    {
        if($this->csvStrategy == self::CSV_STRATEGY_DEFAULT) {
            fputcsv($resource, $value, $delimiter, '"', self::escapeFor(\XmlExportEngine::$exportOptions));
        } else {
            CsvRcfWriter::fputcsv($resource, $value, $delimiter);
        }
    }
}
