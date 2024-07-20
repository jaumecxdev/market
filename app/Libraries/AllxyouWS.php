<?php

namespace App\Libraries;

use App\Category;
use App\Product;
use App\Shop;
use App\ShopFilter;
use App\ShopProduct;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class AllxyouWS extends BagistoWS implements MarketWSInterface
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
        try {
            $shop_id = $this->shop->id;
            $status_id = 1;     // nuevo
            $cost_min = 30;
            $cost_max = 1000;
            $stock_min = 5;

            // INTEGRAR ONLY MPE FILES
            // 13-30	Esprinet:	1%
            // 14-27	Desyman:	Mateixos preus. Alguna vegada inclus a favor de MPe
            // 36		Dmi:		Idiomund no integra DMI
            // 38-23	Megasur:	Mateixos preus

            // INTEGRAR IDIOMUND + MPE
            // 10-29	Vinzeo: 	4.5% - 6%
            // 8-31	    Ingram:		Molta diferència
            // 11-35	Techdata:	Molta diferència    MPE NO INTEGRA


            // NO PORTUGAL: 14-27 desyman, 13-30 Esprinet, 22 Depau, 37 Aseuropa
            // 41 Grutinet
            $supplier_ids = [1, 8, 10, 11, 13, 14, 16, 22, 23, 24, 26, 27, 28, 29, 30, 31, 36, 37, 38, 39];
            //$own_suppliers = [22, 23, 26, 37];        // 22 Depau, 23 Megasur, 26 SCE, 37 Aseuropa
            //$supplier_ids = array_merge($supplier_ids, $own_suppliers);

            $categories_id_1000 = Category::select('categories.id', 'categories.name')
                ->join('products', 'products.category_id', '=', 'categories.id')
                ->whereIn('categories.name', ['Relojes de pulsera y de bolsillo Smartwatches', 'Teléfonos móviles Smartphones',
                    'Televisores', 'Ordenadores portátiles', 'Ordenadores de sobremesa', 'Ordenadores All in one',
                    'Ordenadores tipo Barebone', 'Tablets', 'Ebooks', 'Bicicletas', 'Monopatines', 'Patinetes',
                    'Scooters eléctricos'])
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
                ->whereIn('categories.name', ['Monitores de ordenador'])
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
                ->whereIn('categories.name', ['Discos Duros Externos', 'Teclados', 'Ratones y trackballs',
                    'Afeitadoras y cuchillas', 'Altavoces', 'Aspiradoras', 'Auriculares', 'Cafeteras', 'Cámaras digitales',
                    'Memoria flash', 'Tarjetas de memoria flash', 'Memorias USB', 'Robots de Cocina'])
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

            // 42 jamonrey
            $new_supplier_ids = [42];
            $cost_min = 40;
            $cost_max = 1000;
            $stock_min = 5;
            $new_category_ids = Category::select('categories.id', 'categories.name')
                ->join('products', 'products.category_id', '=', 'categories.id')
                ->whereIn('categories.name', ['Jamón', 'Paletilla'])
                ->whereIn('products.supplier_id', $new_supplier_ids)
                ->where('products.cost', '>=', $cost_min)
                ->where('products.cost', '<=', $cost_max)
                ->where('products.stock', '>=', $stock_min)
                ->groupBy('categories.id')
                ->orderBy('categories.name')
                //->get();
                ->pluck('categories.id')
                ->all();

            foreach ($new_supplier_ids as $new_supplier_id) {
                foreach ($new_category_ids as $new_category_id) {

                    if (Product::whereCategoryId($new_category_id)
                        ->whereSupplierId($new_supplier_id)
                        ->whereNotNull('name')
                        ->where('cost', '>=', $cost_min)
                        ->where('cost', '<=', $cost_max)
                        ->where('stock', '>=', $stock_min)
                        ->count()) {

                        ShopFilter::updateOrCreate([
                            'shop_id'       => $shop_id,
                            'supplier_id'   => $new_supplier_id,
                            'category_id'   => $new_category_id
                        ],[
                            'stock_min'     => $stock_min,
                            'cost_min'      => $cost_min,
                            'cost_max'      => $cost_max,
                            'status_id'     => 1,
                        ]);
                    }
                }
            }


            // 41 Grutinet i altres
            $new_supplier_ids = [1, 8, 10, 11, 13, 14, 16, 22, 23, 24, 26, 27, 28, 29, 30, 31, 36, 37, 38, 39];
            $cost_min = 30;
            $cost_max = 1000;
            $stock_min = 2;
            $new_category_ids = Category::select('categories.id', 'categories.name')
                ->join('products', 'products.category_id', '=', 'categories.id')
                ->whereIn('categories.name', ['Animales de peluche', 'Barbacoas', 'Batería de cocina', 'BDSM Bondage Disciplina Dominación Sumisión Sadismo y Masoquismo',
                    'Braguitas', 'Cacerolas', 'Cafeteras', 'Calzoncillos', 'Cocedores de huevos', 'Condones', 'Flores', 'Fundas de nórdico', 'Herramientas de jardín',
                    'Jamoneros', 'Jardinería', 'Juegos de cubertería', 'Juegos de vajilla', 'Juguetes educativos', 'Juguetes sexuales', 'Lencería erótica',
                    'Lubricantes íntimos', 'Macetas y tiestos', 'Mantas', 'Mobiliario de exterior', 'Mochilas', 'Muñecas', 'Ollas a presión', 'Ollas de hierro',
                    'Planchas y sistemas de planchado', 'Platos', 'Robots de Cocina', 'Ropa de cama', 'Sábanas', 'Sartenes para freír', 'Toallas', 'Toallas de playa',
                    'Toallas de baño y manoplas', 'Vajilla', 'Vibradores y Consoladores', 'Woks', 'Limpiadoras a presión'])
                ->whereIn('products.supplier_id', $new_supplier_ids)
                ->where('products.cost', '>=', $cost_min)
                ->where('products.cost', '<=', $cost_max)
                ->where('products.stock', '>=', $stock_min)
                ->groupBy('categories.id')
                ->orderBy('categories.name')
                //->get();
                ->pluck('categories.id')
                ->all();

            foreach ($new_supplier_ids as $new_supplier_id) {

                //dd($new_supplier_ids, $new_supplier_id);

                foreach ($new_category_ids as $category_id) {
                    if (Product::whereCategoryId($category_id)
                        ->whereSupplierId($new_supplier_id)
                        ->whereNotNull('name')
                        ->where('cost', '>=', $cost_min)
                        ->where('cost', '<=', $cost_max)
                        ->where('stock', '>=', $stock_min)
                        ->count()) {

                        //dd($new_supplier_ids, $new_supplier_id, $category_id, $new_category_ids);

                        ShopFilter::updateOrCreate([
                            'shop_id'       => $shop_id,
                            'supplier_id'   => $new_supplier_id,
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

            return 'Filtros creados correctamente.';

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, null);
        }
    }


}

