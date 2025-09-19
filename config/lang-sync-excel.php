<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Excel File URL
    |--------------------------------------------------------------------------
    |
    | The URL to the Google Sheets document exported as XLSX.
    |
    */
    'excel_url' => env('LANG_SYNC_EXCEL_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Output Disk
    |--------------------------------------------------------------------------
    |
    | The disk to use for saving language files.
    |
    */
    'output_disk' => env('LANG_SYNC_OUTPUT_DISK', 'local'),
];
