<?php

namespace App\Http\Controllers;

use App\Category;
use App\CategoryCanon;
use App\Shop;
use App\ShopParam;
use App\Traits\HelperTrait;
use Artisan;
use Illuminate\Http\Request;
use Throwable;

class CategoryController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        try {
            $params = $request->all();
            if (!isset($params['order_by']) || $params['order_by'] == null) {
                $params['order_by'] = 'categories.path';
                $params['order'] = 'asc';
            }
            $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
            //$categories = Category::has('supplier_categories')->filter($params)->paginate(100);
            //$categories = Category::has('products')->filter($params)->paginate(100);
            $categories = Category::filter($params)->paginate(1000);

            return view('category.index', compact('params', 'order_params', 'categories'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        return view('category.create-edit');
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category_id'       => 'nullable|exists:categories,id',
            'category_name'     => 'nullable',
            'code'              => 'required|max:64',
            'name'              => 'required|max:255',
            'seo_name'          => 'nullable|max:255',
        ]);

        try {
            // category_id, category_name => parent_id, parent_name
            // code, name, seo_name
            if (!$validatedData['category_name']) $validatedData['category_id'] = null;
            if (isset($validatedData['category_id'])) {
                $validatedData['parent_id'] = $validatedData['category_id'];
                $parent = Category::find($validatedData['parent_id']);
                $validatedData['parent_code'] = $parent->code;
                $validatedData['path'] = $parent->path. ' / '.$parent->name;
                $validatedData['level'] = $parent->level + 1;
                $validatedData['leaf'] = true;
            }
            else {
                $validatedData['parent_id'] = null;
                $validatedData['parent_code'] = null;
                $validatedData['path'] = null;
                $validatedData['level'] = 1;
                $validatedData['leaf'] = false;
            }

            unset($validatedData['category_id']);
            unset($validatedData['category_name']);

            // 'parent_id', 'name', 'seo_name', 'path', 'code', 'parent_code', 'level', 'leaf'
            Category::create($validatedData);

            return redirect()->route('categories.index')->with('status', 'Categoria creada correctamente.');

        } catch (Throwable $th) {

            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }


    public function show(Category $category)
    {
        return view('category.create-edit', compact('category'));
    }


    public function edit(Category $category)
    {
        return view('category.create-edit', compact('category'));
    }


    public function update(Request $request, Category $category)
    {
        $validatedData = $request->validate([
            'category_id'       => 'nullable|exists:categories,id',
            'category_name'     => 'nullable',
            'code'              => 'required|max:64',
            'name'              => 'required|max:255',
            'seo_name'          => 'nullable|max:255',
        ]);

        try {
            // category_id, category_name => parent_id, parent_name
            // code, name, seo_name
            if (!$validatedData['category_name']) $validatedData['category_id'] = null;
            if (isset($validatedData['category_id'])) {
                $validatedData['parent_id'] = $validatedData['category_id'];
                $parent = Category::find($validatedData['parent_id']);
                $validatedData['parent_code'] = $parent->code;
                $validatedData['path'] = $parent->path. ' / '.$parent->name;
                $validatedData['level'] = $parent->level + 1;
                $validatedData['leaf'] = true;
            }
            else {
                $validatedData['parent_id'] = null;
                $validatedData['parent_code'] = null;
                $validatedData['path'] = null;
                $validatedData['level'] = 1;
                $validatedData['leaf'] = false;
            }

            unset($validatedData['category_id']);
            unset($validatedData['category_name']);

            // 'parent_id', 'name', 'seo_name', 'path', 'code', 'parent_code', 'level', 'leaf'
            Category::whereId($category->id)->update($validatedData);
            $category->refresh();

            return redirect()->route('categories.index', ['category_name' => $category->name, 'category_id' => $category->id])
                ->with('status', 'Categoria modificada correctamente.');

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }


    public function destroy(Category $category)
    {
        return redirect()->route('categories.index')->with('status', 'No se acepta eliminar categorías.');
    }


    public function import()
    {
        try {
            $msg = Artisan::call('import:google', []);

            return redirect()->route('categories.index')->with('status', $msg);

        } catch (\Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, null);
        }



        /* $client = new Client();
        $xls_file = 'https://www.google.com/basepages/producttype/taxonomy-with-ids.es-ES.xls';
        $res = $client->request('GET', $xls_file);

        if ($res->getStatusCode() == '200') {
            return redirect()->route('categories.index')->withErrors(['errormsg' => __('No Google Taxonomy importing CODE')])->withInput();;

            /$local_xls_file = 'admin/taxonomy-with-ids.es-ES.xls';
            Storage::put($local_xls_file, $res->getBody());
            Excel::import(new GoogleTaxonomyImport(), storage_path('app/' .$local_xls_file), null, \Maatwebsite\Excel\Excel::XLS);
            return redirect()->route('categories.index')->with('status', __('Google Taxonomy imported'));
        }
        else
            return redirect()->route('categories.index')->withErrors(['errormsg' => __('Google Taxonomy importing error')])->withInput(); */
    }


    public function canons(Request $request)
    {
        try {
            $category_canons = CategoryCanon::select(
                'category_canons.*',
                'categories.code as category_code',
                'categories.name as category_name'
            )
            ->leftJoin('categories', 'categories.id', '=', 'category_canons.category_id')
            ->orderBy('categories.name')
            ->get();

            return view('category.canons', compact('category_canons'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }


    /* public function syncCanons()
    {
        try {
            $shops = Shop::whereEnabled('1')->get();     //->pluck('id');
            $category_canons = CategoryCanon::get();
            foreach ($shops as $shop) {
                foreach ($category_canons as $category_canon) {

                    if (!isset($shop->locale) || $shop->locale == $category_canon->locale)
                        ShopParam::updateOrCreate([
                            'shop_id'       => $shop->id,
                            //'supplier_id'   => $this->id,
                            //'supplierSku'   => $supplier_param->supplierSku,
                            //'brand_id'      => $supplier_param->brand_id,
                            'category_id'   => $category_canon->category_id,
                        ],[
                            'canon'         => $category_canon->canon,
                        ]);
                }
            }

            return redirect()->route('categories.canons')->with('status', 'Cánones añadidos a Todas las Tiendas activas');

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$shops, $category_canons]);
        }
    } */

}
