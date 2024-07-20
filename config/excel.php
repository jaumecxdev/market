<?php

/* use Maatwebsite\Excel\Excel;

return [

    'exports' => [


        'chunk_size'             => 1000,


        'pre_calculate_formulas' => false,


        'csv'                    => [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'line_ending'            => PHP_EOL,
            'use_bom'                => false,
            'include_separator_line' => false,
            'excel_compatibility'    => false,
        ],
    ],

    'imports'            => [

        'read_only' => true,

        'heading_row' => [


            'formatter' => 'slug',
        ],


        'csv'         => [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'escape_character'       => '\\',
            'contiguous'             => false,
            'input_encoding'         => 'UTF-8',
        ],
    ],


    'extension_detector' => [
        'xlsx'     => Excel::XLSX,
        'xlsm'     => Excel::XLSX,
        'xltx'     => Excel::XLSX,
        'xltm'     => Excel::XLSX,
        'xls'      => Excel::XLS,
        'xlt'      => Excel::XLS,
        'ods'      => Excel::ODS,
        'ots'      => Excel::ODS,
        'slk'      => Excel::SLK,
        'xml'      => Excel::XML,
        'gnumeric' => Excel::GNUMERIC,
        'htm'      => Excel::HTML,
        'html'     => Excel::HTML,
        'csv'      => Excel::CSV,
        'tsv'      => Excel::TSV,


        'pdf'      => Excel::DOMPDF,
    ],

    'value_binder' => [


        'default' => Maatwebsite\Excel\DefaultValueBinder::class,
    ],

    'transactions' => [


        'handler' => 'db',
    ],

    'temporary_files' => [


        'local_path'  => sys_get_temp_dir(),


        'remote_disk' => null,

    ],
];
 */
