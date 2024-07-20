<?php

namespace App\Console\Commands;

use App\Category;
use App\LogSchedule;
use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;


class GoogleImport extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:google';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene las categorías de Google Taxonomy y las integra en la plataforma.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $res = [];
            $news = [];
            Log::channel('commands')->info('START import:google');
            $log_schedule = LogSchedule::create(['type' => 'import:google', 'name' => 'import:google']);

            $rootTaxonomyCodes = ['1', '8', '111', '141', '166', '222', '412', '436', '469', '536',
                '537', '632', '772', '783', '888', '922', '988', '1239', '2092', '5181', '5605'];

            $cols = [
                0   => 'A',
                1   => 'B',
                2   => 'C',
                3   => 'D',
                4   => 'E',
                5   => 'F',
                6   => 'G',
                7   => 'H'
            ];

            $file_rows = $this->getRows();

            $count = 0;
            foreach ($file_rows as $row) {

                $taxonomyCode = $row['A'];
                $rootTaxonomyName = $row['B'];

                // Root Category
                if (in_array($taxonomyCode, $rootTaxonomyCodes)) {

                    // Update Root Category
                    $root_category = Category::updateOrCreate([
                        'code'              => $taxonomyCode,
                    ],[
                        'name'              => $rootTaxonomyName,
                        'parent_id'         => null,
                        'path'              => null,
                        'parent_code'       => null,
                        'level'             => 1,
                        'leaf'              => false,
                    ]);

                    $count++;

                    $msg = 'Root Category: (' .$taxonomyCode. ') '.$rootTaxonomyName;
                    Log::channel('commands')->info($msg);
                    $res[] = $msg;
                }
                else {
                    // 1st is Root Category
                    if ($parent_category = Category::where('name', $rootTaxonomyName)->first()) {

                        $finish = false;
                        $path = $rootTaxonomyName;                  // Alimentación, bebida y tabaco
                        $previousTaxonomyName = $rootTaxonomyName;  // Alimentación, bebida y tabaco
                        $row_index = 2;
                        $taxonomyName = $row['C'];                  // Alimentos
                        while (!$finish && $row_index < 7) {
                            $row_index++;                           // 3 -> D

                            $nextTaxonomyName = $row[$cols[$row_index]]; // '' | Aliños y especias
                            if ($nextTaxonomyName == '') {
                                $finish = true;
                            }
                            else {
                                // 'parent_id', 'name', 'seo_name', 'path', 'code', 'parent_code', 'level', 'leaf'
                                $parent_category = Category::whereName($taxonomyName)->first();

                                /* $parent_category = DB::table('categories')->select('categories.*')
                                    ->leftJoin('categories as parent_categories', 'categories.parent_id', '=', 'parent_categories.id')
                                    ->where('categories.name', $taxonomyName)
                                    ->where('parent_categories.name', $previousTaxonomyName)
                                    ->first(); */

                                $path .= ' / ' .$taxonomyName;          // Apparel & Accessories / Clothing,
                                $previousTaxonomyName = $taxonomyName;  // Clothing,
                                $taxonomyName = $nextTaxonomyName;      // Activewear,
                            }
                        }

                        // Update LEAF Category
                        if ($taxonomyName != '') {

                            //if (!$category = Category::firstwhere('code', $taxonomyCode)) $news[] = [$taxonomyCode, $taxonomyName, $path];
                            $category = Category::updateOrCreate([
                                'code'              => $taxonomyCode,
                            ],[
                                'name'              => $taxonomyName,
                                'parent_id'         => $parent_category->id ?? null,
                                'path'              => $path,
                                'parent_code'       => $parent_category->code ?? null,
                                'level'             => $row_index - 1,
                                'leaf'              => true,
                            ]);

                            $count++;

                            $msg = 'Category: (' .$taxonomyCode. ') '.$path.' / '.$taxonomyName;
                            Log::channel('commands')->info($msg);
                            $res[] = $msg;
                        }
                        else {
                            $msg = 'ERROR TAXONOMYNAME == EMPTY';
                            Log::channel('commands')->error($msg);
                            $res[] = $msg;
                        }

                    }
                }
            }

            $filename = date('Y-m-d_H'). '_update_categories.json';
            $directory = 'google/';
            Storage::put($directory.$filename, json_encode($res));

            Log::channel('commands')->info('END import:google');
            $log_schedule->update(['ends_at' => now(), 'info' => $count]);

            return 'Actualizadas '.$count.' categorías desde Google Taxonomy. '.$directory.$filename;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, 'GoogleImport', $res);
        }
    }


    private function getRows()
    {
        try {
            // https://www.google.com/basepages/producttype/taxonomy-with-ids.es-ES.xls
            $client = new Client(['base_uri' => 'https://www.google.com/']);
            $response = $client->get('basepages/producttype/taxonomy-with-ids.es-ES.xls', []);
            if ($response->getStatusCode() == '200' && $contents = $response->getBody()->getContents()) {
                $filename = date('Y-m-d_H'). '_taxonomy-with-ids.es-ES.xls';
                $directory = 'google/';
                Storage::makeDirectory($directory);
                Storage::put($directory.$filename, $contents);
            }

            if (isset($contents)) {

                $inputFileName = storage_path('app/'.$directory.$filename);
                $inputFileType = IOFactory::identify($inputFileName);   // 'Xlsx'
                $reader = IOFactory::createReader($inputFileType);
                $spreadsheet = $reader->load($inputFileName);
                $sheet = $spreadsheet->getSheet(0);

                return $sheet->toArray(null, true, true, true);
            }
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this);
        }
    }


}
