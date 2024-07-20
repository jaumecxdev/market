<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LogsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        // put, append, delete, exists, get, readStream, writeStream, copy, move, size,
        // files, allFiles, directories, AllDirectories, makeDirectory, deleteDirectory
        // https://laravel.com/api/6.x/Illuminate/Contracts/Filesystem/Filesystem.html
        //Storage::disk('public')->exists('mp/ae/errors/file.jpg');
        try {
            $dir = $request->keys()[0] ?? null;
            $current = $dir ?? '/';

            // Directories
            $dirs = null;
            $dir_names = Storage::directories($dir);
            if ($dir !== null) {
                $params = (strlen($dir) == strlen(basename($dir)) ? '' : substr($dir, 0, strlen($dir)-strlen(basename($dir))-1));
                $dirs[] = [
                    'name'  => '..',
                    'href'  => route('logs') .'?'.$params  //$dir.'/__',
                ];
            }

            foreach ($dir_names as $dir_name) {
                $dirs[] = [
                    'name'  => $dir_name,
                    'href'  => route('logs') .'?'.$dir_name,
                ];
            }

            // Files
            $files = null;
            $file_names = Storage::files($dir);
            rsort($file_names);
            foreach ($file_names as $file_name) {
                $files[] = [
                    'name'  => $file_name,
                    'href'  => route('logs.view').'?file='.urlencode($file_name),
                ];
            }

            return view('logs.index', compact('current', 'dirs', 'files'));

        }
        catch (Throwable $th) {

        }
    }



    public function view(Request $request)
    {
        try {
            $filename = $request->input('file') ?? null;
            $type = $request->input('type') ?? null;
            if (isset($filename)) {
                if ($type == 'log') $file_contents = Storage::disk('logs')->get($filename);
                else $file_contents = Storage::get($filename);

                //header('Content-Type: application/json');
                //return response()->json($file_contents);
                return view('logs.view', compact('filename', 'file_contents'));
            }
        }
        catch (Throwable $th) {
        }

        return redirect()->back()->with('status', 'File not found.');
    }


    public function errors()
    {
        try {
            // Logs Errors
            $logs_list = null;
            $logs_errors = Storage::disk('logs')->files();
            if (!empty($logs_errors)) {
                rsort($logs_errors);
                $count = 0;
                foreach ($logs_errors as $logs_error) {
                    if ($count > 20) break;
                    $logs_list[] = [
                        'name'  => $logs_error,
                        'href'  => route('logs.view').'?type=log&file='.urlencode($logs_error),
                    ];

                    $count++;
                }
            }

            // Marketplaces Errors
            $mp_list = null;
            $mp_dir_names = Storage::directories('mp');
            foreach ($mp_dir_names as $mp_dir_name) {
                $mp_errors = Storage::files($mp_dir_name.'/errors');
                $mp_name = substr($mp_dir_name, 3, strlen($mp_dir_name));
                $mp_list[$mp_name] = null;
                if (!empty($mp_errors)) {
                    rsort($mp_errors);
                    $count = 0;
                    foreach ($mp_errors as $mp_error) {

                        if ($count > 5) break;
                        $mp_error_info = pathinfo($mp_error);
                        $mp_list[$mp_name][] = [
                            'name'  => $mp_error_info['filename'].'.'.$mp_error_info['extension'],
                            'href'  => route('logs.view').'?file='.urlencode($mp_error),
                        ];

                        $count++;
                    }
                }
            }

            // Suppliers Errors
            $supplier_list = null;
            $supplier_dir_names = Storage::directories('supplier');
            foreach ($supplier_dir_names as $supplier_dir_name) {
                $supplier_errors = Storage::files($supplier_dir_name.'/errors');
                $supplier_name = substr($supplier_dir_name, 9, strlen($supplier_dir_name));
                $supplier_list[$supplier_name] = null;
                if (!empty($supplier_errors)) {
                    rsort($supplier_errors);
                    $count = 0;
                    foreach ($supplier_errors as $supplier_error) {

                        if ($count > 5) break;
                        $supplier_error_info = pathinfo($supplier_error);
                        $supplier_list[$supplier_name][] = [
                            'name'  => $supplier_error_info['filename'].'.'.$supplier_error_info['extension'],
                            'href'  => route('logs.view').'?file='.urlencode($supplier_error),
                        ];

                        $count++;
                    }
                }
            }

            return view('logs.errors', compact('logs_list', 'mp_list', 'supplier_list'));

        }
        catch (Throwable $th) {
        }
    }


}
