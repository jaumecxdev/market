<?php

namespace App\Http\Controllers;

use App\LogSchedule;
use App\Shop;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Throwable;

class LogScheduleController extends Controller
{
    use HelperTrait;

    const SUPPLIER_IDS = [
        1,  // blanes
       /*  8,  // Idiomund Ingram
        10, // Idiomund Vinzeo
        11, // Idiomund Techdata
        13, // Idiomund esprinet
        14, // Idiomund desyman */
        16, // MCR
        22, // depau
        23, // megasur
        24, // Globomatik
        26, // SCE
        27, // Desyman
        28, // Sppedler
        29, // Vinzeo
        30, // Esprinet
        31, // Ingram
        //35, // Techdata
        36, // DMI
        37, // Aseuropa
        //38, // Idiomund Megasur
        39, // Infortisa
        41, // Grutinet
    ];

    const SUPPLIER_LOG_TYPES = ['get:products', 'update:products'];
    const SHOPS_LOG_TYPES = ['post:prices', 'post:updated', 'get:buybox', 'get:orders'];
    const NOTIFICATION_LOG_TYPE = ['send:notifications'];
    const OTHER_LOG_TYPES = ['reset:logs', 'telescope:prune', 'update:voxapi', 'copy:attributes', 'upgrade:feeds', 'clean:feeds', 'sync:allparams'];

    public function index(Request $request)
    {
        try {
            $params = $request->all();
            if (!isset($params['order_by']) || $params['order_by'] == null) {
                $params['order_by'] = 'log_schedules.ends_at';
                $params['order'] = 'asc';
            }
            $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);

            return view('log_schedule.index', compact('params', 'order_params'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request);
        }
    }

}
