<?php

namespace App\Http\Controllers\Saas;


use App\Currency;
use App\Status;
use App\Traits\HelperTrait;
use App\Type;
use App\Category;
use App\Image;
use App\Brand;
use App\Http\Controllers\Controller;
use App\Product;
use App\ShopFilter;
use App\ShopParam;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\MpeExcel as FacadesMpeExcel;
use Throwable;


class ProductController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        try {
            $statuses = Status::where('type', 'product')->get();

            //$params = $request->except('_token');
            $params = $request->all();
            if (!isset($params['order_by']) || $params['order_by'] == null) {
                $params['order_by'] = 'products.created_at';
                $params['order'] = 'desc';
            }
            $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);

            $user = $request->user();
            $suppliers = $user->getSuppliers();
            $products = Product::filter($params)
                ->whereNull('products.parent_id')
                ->whereIn('products.supplier_id', $user->getSuppliersId())
                ->paginate(100);

            if (!$products)
                return $this->backWithErrorMsg(__METHOD__, 'Error en los filtros', $request->all());

            return view('saas.product.index', compact('products', 'statuses', 'suppliers', 'params', 'order_params'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }


    public function export(Request $request)
    {
        try {
            $params = $request->all();
            $products = Product::filter($params)->whereNull('products.parent_id')->get();
            FacadesMpeExcel::download($products);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }


    public function create()
    {
        $suppliers = Auth::user()->getSuppliers();
        $brands = Brand::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name', 'desc')->get();
        $statuses = Status::where('type', 'product')->orderBy('name', 'asc')->get();
        $types = Type::where('type', 'product')->orderBy('name', 'asc')->get();
        $currencies = Currency::orderBy('name', 'asc')->get();

        return view('saas.product.create-edit', compact('suppliers', 'brands', 'categories', 'statuses', 'types', 'currencies'));
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'product_id'    => 'nullable|exists:products,id',       // parent_id
            'supplier_brand_id'      => 'nullable|exists:supplier_brands,id',
            'supplier_brand_name'    => 'nullable|max:255',
            'supplier_category_id'   => 'nullable|exists:supplier_categories,id',
            'supplier_category_name' => 'nullable|max:255',
            'status_id'     => 'required|exists:statuses,id',
            'currency_id'   => 'required|exists:currencies,id',

            'name'          => 'nullable|max:255',
            'keywords'      => 'nullable|max:255',
            'pn'            => 'nullable|max:64',
            'ean'           => 'nullable|max:64',
            'upc'           => 'nullable|max:64',
            'isbn'          => 'nullable|max:64',
            'gtin'          => 'nullable|max:64',
            'shortdesc'     => 'nullable|min:3|max:4096',
            'longdesc'      => 'nullable',

            'weight'        => 'nullable|max:64',
            'length'        => 'nullable|max:64',
            'width'         => 'nullable|max:64',
            'height'        => 'nullable|max:64',

            'supplierSku'   => 'nullable|max:64',
            'model'         => 'nullable|max:64',
            'cost'          => 'required|numeric|gte:0',
            'tax'           => 'required|numeric|gte:0',
            'stock'         => 'required|numeric|gte:0',

            'size'          => 'nullable|max:64',
            'color'         => 'nullable|max:64',
            'material'      => 'nullable|max:64',
            'style'         => 'nullable|max:64',
            'gender'        => 'nullable|max:64',
        ]);

        $validatedData['ready'] = $request->has('ready') ? 1 : 0;
        $validatedData['fix_text'] = $request->has('fix_text') ? 1 : 0;
        unset($validatedData['supplier_brand_name']);
        unset($validatedData['supplier_category_name']);

        Product::create($validatedData);

        return redirect()->route('saas.products')->with('status', 'Producto creado correctamente.');
    }


    public function show(Product $product)
    {
        return view('saas.product.show', compact('product'));
    }


    public function edit(Product $product)
    {
        $suppliers = Auth::user()->getSuppliers();
        $statuses = Status::where('type', 'product')->orderBy('name', 'asc')->get();
        $currencies = Currency::orderBy('name', 'asc')->get();

        return view('saas.product.create-edit', compact('product', 'suppliers', 'statuses', 'currencies'));
    }


    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'product_id'    => 'nullable|exists:products,id',       // parent_id
            'supplier_brand_id'      => 'nullable|exists:supplier_brands,id',
            'supplier_brand_name'    => 'nullable|max:255',
            'supplier_category_id'   => 'nullable|exists:supplier_categories,id',
            'supplier_category_name' => 'nullable|max:255',
            'status_id'     => 'required|exists:statuses,id',
            'currency_id'   => 'required|exists:currencies,id',

            'name'          => 'nullable|max:255',
            'keywords'      => 'nullable|max:255',
            'pn'            => 'nullable|max:64',
            'ean'           => 'nullable|max:64',
            'upc'           => 'nullable|max:64',
            'isbn'          => 'nullable|max:64',
            'gtin'          => 'nullable|max:64',
            'shortdesc'     => 'nullable|min:3|max:4096',
            'longdesc'      => 'nullable',

            'weight'        => 'nullable|max:64',
            'length'        => 'nullable|max:64',
            'width'         => 'nullable|max:64',
            'height'        => 'nullable|max:64',

            'supplierSku'   => 'nullable|max:64',
            'model'         => 'nullable|max:64',
            'cost'          => 'required|numeric|gte:0',
            'tax'           => 'required|numeric|gte:0',
            'stock'         => 'required|numeric|gte:0',

            'size'          => 'nullable|max:64',
            'color'         => 'nullable|max:64',
            'material'      => 'nullable|max:64',
            'style'         => 'nullable|max:64',
            'gender'        => 'nullable|max:64',
        ]);

        $validatedData['ready'] = $request->has('ready') ? 1 : 0;
        $validatedData['fix_text'] = $request->has('fix_text') ? 1 : 0;
        unset($validatedData['supplier_brand_name']);
        unset($validatedData['supplier_category_name']);

        Product::whereId($product->id)->update($validatedData);

        return redirect()->route('saas.products.show', compact('product'))->with('status', 'Producto modificado correctamente.');
    }


    public function destroy(Product $product)
    {
        try {
            if ($product->deleteSecure())
                return redirect()->route('saas.products')->with('status', 'Producto eliminado.');

            return redirect()->route('saas.products')->with('status', 'No se ha podido eliminar el prodducto. Revisa los filtros, parámetros y ofertas de tiendas.');

        } catch (QueryException $e) {
            return redirect()->route('saas.products')->with('status', $e->getMessage());
        }
    }


    // PRODUCT IMAGES

    public function images(Product $product)
    {
        $image = $product->images()->orderBy('type')->first();

        return view('saas.product.images', compact('product', 'image'));
    }


    public function storeImages(Request $request, Product $product)
    {
        request()->validate([
            //'image' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:4096'
        ]);

        $check = true;

        // Delete old image
        if ($image_delete = Image::whereProductId($product->id)->first()) {
            $check = Image::destroy($image_delete->id);
            if ($check) $check = Storage::delete('public/img/' .$product->id. '/' .$image_delete->src);
        }

        // Add new image
        if ($image = $request->file('image')) {
            $product->updateOrStoreImage($image);
        }

        $status = $check ? 'Imágen guardada correctamente.' : 'Ocurrió un error al guardar la imágen.';
        return back()->with('status', $status);
    }


    // PRODUCT MARKETS

    public function shops(Product $product)
    {
        $shop_products_count = $product->shop_products()->count();
        $shop_products = $product->shop_products;

        return view('saas.product.shops', compact('product', 'shop_products_count', 'shop_products'));
    }


    public function addToShopUpdate(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'shop_id'       => 'required|exists:shops,id',

            'fee'           => 'nullable|numeric|gte:0',
            'bfit_min'      => 'nullable|numeric|gte:0',
            'mps_fee'       => 'nullable|numeric|gte:0',
            'price'         => 'nullable|numeric|gte:0',
            'stock'         => 'nullable|numeric|gte:0',
            'stock_min'     => 'nullable|numeric|gte:0',
            'stock_max'     => 'nullable|numeric|gte:0',

            'starts_at'     => 'nullable|date_format:Y-m-d',
            'ends_at'       => 'nullable|date_format:Y-m-d',
        ]);

        // Añadir Filtro
        $validatedData['product_id'] = $product->id;
        ShopFilter::updateOrCreate([
            'shop_id'           => $validatedData['shop_id'],
            'product_id'        => $validatedData['product_id'],
        ],[]);

        // Añadir Parametro a Tienda
        if (isset($validatedData['fee']) || isset($validatedData['bfit_min']) || isset($validatedData['price']) ||
            isset($validatedData['stock']) || isset($validatedData['stock_min']) || isset($validatedData['stock_max'])) {

            ShopParam::updateOrCreate([
                'shop_id'           => $validatedData['shop_id'],

                'product_id'        => $validatedData['product_id'],
                /* 'supplierSku'       => $validatedData['supplierSku'],
                'pn'                => $validatedData['pn'] ?? null,
                'ean'               => $validatedData['ean'] ?? null,
                'upc'               => $validatedData['upc'] ?? null,
                'isbn'              => $validatedData['isbn'] ?? null,
                'gtin'              => $validatedData['gtin'] ?? null, */

                'starts_at'         => $validatedData['starts_at'] ?? null,
                'ends_at'           => $validatedData['ends_at'] ?? null,

                'fee'               => $validatedData['fee'] ?? 0,
                'bfit_min'          => $validatedData['bfit_min'] ?? 0,
                'mps_fee'           => $validatedData['mps_fee'] ?? 0,
                'price'             => $validatedData['price'] ?? 0,
                'stock'             => $validatedData['stock'] ?? 0,
                'stock_min'         => $validatedData['stock_min'] ?? 0,
                'stock_max'         => $validatedData['stock_max'] ?? 0,
            ],[
            ]);
        }

        return redirect()->route('saas.products')->with('status', 'Filtro añadido a la Tienda.');
    }


}
