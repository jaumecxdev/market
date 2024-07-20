<?php

namespace App\Facades;

use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use ZipArchive;

class MpeImport
{
    use HelperTrait;

    /* const IMPORTS = ['ImportArrovi', 'ImportTone', 'ImportAliexpress', 'ImportWorten',
        'ImportValorista', 'ImportGlobomatik', 'ImportMegasur', 'ImportDepau', 'ImportSCE', 'ImportDesyman', 'ImportVinzeo']; */


    static function getImportsLibraries()
    {
        $import_libraries = array_diff(scandir(base_path().'/app/Imports'), ['.', '..']);
        array_walk($import_libraries, function(&$value, $key) {
            $value = mb_substr($value, 0, strlen($value)-4);
        });

        return $import_libraries;
    }


    public function getRowsUploaded(UploadedFile $uploaded_file, $header_rows)
    {
        try {
            return $this->getFileRows($uploaded_file->getPathname(), $header_rows);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $uploaded_file->getPathname());
        }
    }



    public function getRowsUri($uri, $header_rows, $directory, $filename)
    {
        try {
            // https://www.google.com/basepages/producttype/taxonomy-with-ids.es-ES.xls
            $client = new Client();     //(['base_uri' => 'https://www.google.com/']);
            $response = $client->get($uri, ['headers' => ['Content-Type' => 'text/plain']]);
            if ($response->getStatusCode() == '200' && $contents = $response->getBody()->getContents()) {
                Storage::makeDirectory($directory);
                // Si NO utf8_encode -> Camps amb accents = FALSE
                Storage::put($directory.$filename, utf8_encode($contents));
                unset($contents);
                $inputFileName = storage_path('app/'.$directory.$filename);

                return $this->getFileRows($inputFileName, $header_rows);
            }

            return $this->nullAndStorage(__METHOD__, [$uri, $directory, $filename]);
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$uri, $directory, $filename]);
        }
    }


    public function getRowsUriXML($uri, $header_rows, $directory, $filename)
    {
        try {
            // Ignores SSL Verification: CURL: CURLE_PEER_FAILED_VERIFICATION
            // cURL error 60: SSL certificate problem: certificate has expired (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)
            $client = new Client(['verify' => false ]);
            $response = $client->get($uri, ['headers' => ['Content-Type' => 'text/xml']]);
            if ($response->getStatusCode() == '200' && $contents = $response->getBody()->getContents()) {
                Storage::makeDirectory($directory);
                Storage::put($directory.$filename, $contents);

                return simplexml_load_string($contents);
            }

            return $this->nullAndStorage(__METHOD__, [$uri, $directory, $filename]);
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$uri, $directory, $filename]);
        }
    }


    public function getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename, $zipped = false)
    {
        try {
            //$m = memory_get_usage();
            if ($contents = Storage::disk($ftp_disk)->get($ftp_filename)) {
                Storage::makeDirectory($directory);
                // Si NO utf8_encode -> Camps amb accents = FALSE
                $datted_ftp_filename = date('Y-m-d_H').'_'.$ftp_filename;
                if (!$zipped) $contents = utf8_encode($contents);           // Save AS UTF-8 "\xEF\xBB\xBF".$contents
                // offers/2021-07-24_07_TD_ES_773202_A_20210724.zip
                Storage::put($directory.$datted_ftp_filename, $contents);
                unset($contents);
                // /var/www/html/market/storage/app/supplier/techdata/offers/GM_ES_C_Prices20210724.txt
                $inputFileName = storage_path('app/'.$directory.$datted_ftp_filename);

                if ($zipped) {
                    $zip = new ZipArchive;
                    if ($zip->open($inputFileName) === TRUE && $unzipped = $zip->extractTo(storage_path('app/'.$directory))) {
                        $zip->close();
                        $inputFileName = storage_path('app/'.$directory).$filename;
                        $contents = Storage::get($directory.$filename);
                        $contents = utf8_encode($contents);
                        // supplier/techdata/offers/  GM_ES_C_Prices20210724.txt
                        Storage::put($directory.$filename, $contents);
                        unset($contents);
                    }
                }

                //$mm = memory_get_usage();
                return $this->getFileRows($inputFileName, $header_rows);
            }

            return $this->nullAndStorage(__METHOD__, [$ftp_disk, $ftp_filename, $directory, $filename]);
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$ftp_disk, $ftp_filename, $directory, $filename]);
        }
    }


    public function getFileRows($inputFileName, $header_rows)
    {
        try {
            //$m = memory_get_usage();
            $inputFileType = IOFactory::identify($inputFileName);   // 'Xlsx'
            if (!in_array($inputFileType,  ['Csv', 'csv', 'Txt', 'txt', 'Xlsx', 'xlsx', 'Xls', 'xls'])) {
                return $this->msgAndStorage(__METHOD__, 'Tipo de fichero erróneo: '.$inputFileType,
                    [$inputFileName, $header_rows, 'Tipo de fichero erróneo: '.$inputFileType]);
            }

            $reader = IOFactory::createReader($inputFileType);
            $spreadsheet = $reader->load($inputFileName);
            $sheet = $spreadsheet->getSheet(0);
            $file_rows = $sheet->toArray(null, false, true, true);
            unset($sheet);
            unset($spreadsheet);
            if (count($file_rows)) {
                // Remove Headers
                for($i=0; $i<$header_rows; $i++)
                    array_shift($file_rows);
            }

            //$mm = memory_get_usage();

            return $file_rows;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$inputFileName, $header_rows, $inputFileType ?? null, $reader ?? null]);
        }
    }

}
