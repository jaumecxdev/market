<?php

namespace App\Libraries;

use App\Category;
use App\Product;
use App\Shop;
use App\ShopFilter;
use App\ShopProduct;
use Illuminate\Database\Eloquent\Collection;


class LbdcWS extends BagistoWS implements MarketWSInterface
{
    const DEFAULT_CONFIG = [];

    public function __construct(Shop $shop)
    {
        parent::__construct($shop);
    }


    public function getItemRowOffer(ShopProduct $shop_product, $extra_data = [])
    {

    }


    public function getCollectionOffers(Collection $shop_products, $extra_data = [])
    {

    }


    public function setDefaultShopFilters()
    {
        $res = [];

        // SPEEDLER: NO INTEGRAR ENLLOC
        $shop_id = $this->shop->id;
        $status_id = 1;     // nuevo
        $cost_min = 30;
        $cost_max = 1000;
        $stock_min = 5;
        $supplier_ids = [1, 8, 10, 11, 14, 16, 27, 29, 31, 35];   // 27 desyman, 30 Esprinet, 22 Depau, 37 Aseuropa
        //$supplier_id = Supplier::whereCode('mcr')->pluck('id')->first();
        $categories_id_1000 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            // Tabletas gráficas
            ->whereNotIn('categories.name', ['Cámaras de vigilancia','Ordenadores All in one','Ordenadores de sobremesa','Ordenadores portátiles',
                'Patinetes','Proyectores','Relojes de pulsera y de bolsillo Smartwatches','Teléfonos móviles Smartphones','Televisores'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_500 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Altavoces','Altavoces de repuesto para portátiles','Auriculares','Cámaras','Cámaras de vídeo',
                'Cuidado de la salud','Discos Duros Externos','Enrutadores inalámbricos','Escáneres','Gafas 3D','Mandos a distancia',
                'Monitores de ordenador','Productos del hogar','Puntos de acceso inalámbrico, Amplificadores y Repetidores de Red',
                'Redes','Sillas de oficina','Sistemas DJ'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_300 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Cámaras web Webcams','Impresoras Fotográficas','Impresoras Inyección de Tinta','Impresoras Láser',
                'Impresoras Multifuncionales','Impresoras, fotocopiadoras y faxes','Maletines','Memorias USB','Micrófonos','Ratones y trackballs',
                'Tarjetas de memoria flash','Teclados'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_100 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Hornos','Frigoríficos','Electrodomésticos'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_stock_20 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Tarjetas de vídeo'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $filter_groups = [
            [
                'cost_max'      => 1000,
                'category_ids'  => $categories_id_1000
            ],
            [
                'cost_max'      => 500,
                'category_ids'  => $categories_id_500
            ],
            [
                'cost_max'      => 300,
                'category_ids'  => $categories_id_300
            ],
            [
                'cost_max'      => 100,
                'category_ids'  => $categories_id_100
            ],
        ];

        foreach ($supplier_ids as $supplier_id) {

            foreach ($filter_groups as $filter_group) {

                $cost_max = $filter_group['cost_max'];
                $category_ids = $filter_group['category_ids'];

                foreach ($category_ids as $category_id) {
                    if (Product::whereCategoryId($category_id)
                        ->whereSupplierId($supplier_id)
                        ->whereNotNull('name')
                        ->where('cost', '>=', $cost_min)
                        ->where('cost', '<=', $cost_max)
                        ->where('stock', '>=', $stock_min)
                        ->count()) {

                        ShopFilter::updateOrCreate([
                            'shop_id'       => $shop_id,
                            'supplier_id'   => $supplier_id,
                            'category_id'   => $category_id
                        ],[
                            'stock_min'     => $stock_min,
                            'cost_min'      => $cost_min,
                            'cost_max'      => $cost_max,
                            'status_id'     => 1,
                        ]);
                    }
                }
            }
        }


        // Tarjetas de vídeo
        foreach ($supplier_ids as $supplier_id) {

            $cost_max = 300;
            $category_ids = $categories_stock_20;
            $stock_min = 20;

            foreach ($category_ids as $category_id) {
                if (Product::whereCategoryId($category_id)
                    ->whereSupplierId($supplier_id)
                    ->whereNotNull('name')
                    ->where('cost', '>=', $cost_min)
                    ->where('cost', '<=', $cost_max)
                    ->where('stock', '>=', $stock_min)
                    ->count()) {

                    ShopFilter::updateOrCreate([
                        'shop_id'       => $shop_id,
                        'supplier_id'   => $supplier_id,
                        'category_id'   => $category_id
                    ],[
                        'stock_min'     => $stock_min,
                        'cost_min'      => $cost_min,
                        'cost_max'      => $cost_max,
                        'status_id'     => 1,
                    ]);
                }
            }
        }


        dd('FI');

        return redirect()->route('shops.shop_filters.index', [$this->shop])->with('status', 'Filtros creados correctamente.');
    }


}

