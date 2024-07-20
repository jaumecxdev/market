<?php

namespace App\Http\Controllers;


use App\Currency;
use App\Status;
use App\Traits\HelperTrait;
use App\Type;
use App\Attribute;
use App\Category;
use App\Image;
use App\Brand;
use App\Product;
use App\ProviderAttribute;
use App\ProviderCategory;
use App\Shop;
use App\ShopFilter;
use App\ShopParam;
use App\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
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
            if ($user->hasRole('seller')) {
                $suppliers_id = $user->getSuppliersId();
                $suppliers = Supplier::orderBy('name', 'asc')->find($suppliers_id);
                $products = Product::filter($params)->whereNull('products.parent_id')->whereIn('products.supplier_id', $suppliers_id)->paginate(100);
            }
            else {
                $suppliers = Supplier::orderBy('name', 'asc')->get();
                $products = Product::filter($params)->whereNull('products.parent_id')->paginate(100);
            }

            if (!$products)
                return $this->backWithErrorMsg(__METHOD__, 'Error en los filtros', $request->all());

            $provider_filters = $this->getProviderFilters($params);

            return view('product.index', compact('products', 'statuses', 'suppliers', 'params', 'order_params', 'provider_filters'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $request->all());
        }
    }


    private function buildProviderFilterCategory(&$provider_filters, ProviderCategory $provider_category, $type = 'SELECT')
    {
        $provider_filters['provider_category_id'][$provider_category->categoryL3][$provider_category->categoryL4][$type] = $provider_category->id;
    }


    private function buildProviderFilterAttribute(&$provider_filters, ProviderAttribute $provider_attribute, $type = 'SELECT')
    {
        $provider_filters['provider_attribute_value_id'][$provider_attribute->provider_attribute_name][$provider_attribute->provider_attribute_value_name][$type] = $provider_attribute->provider_attribute_value_id;
    }


    private function getProviderAttributesBycategory($provider_category_id, $provider_attributes_selected_ids = null)
    {
        try {
            $provider_attributes = ProviderAttribute::select(
                    'provider_attributes.name as provider_attribute_name',
                    'provider_attribute_values.id as provider_attribute_value_id',
                    'provider_attribute_values.name as provider_attribute_value_name'
                )
                ->leftJoin('provider_attribute_values', 'provider_attribute_values.provider_attribute_id', '=', 'provider_attributes.id')
                ->where('provider_attributes.provider_category_id', $provider_category_id)
                //->whereNotIn('provider_attributes.id', $provider_attributes_selected_ids)
                ->whereNotNull('provider_attribute_values.name')
                ->where('provider_attributes.enabled', 1)
                ->groupBy('provider_attributes.name')
                ->groupBy('provider_attribute_values.id')
                ->groupBy('provider_attribute_values.name');

            if ($provider_attributes_selected_ids)
                $provider_attributes->whereNotIn('provider_attributes.id', $provider_attributes_selected_ids);

            return $provider_attributes->get();

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, [$provider_category_id, $provider_attributes_selected_ids]);
            return[];
        }
    }


    private function getProviderFilters(&$params)
    {
        try {
            $provider_filters = [];
            if (!isset($params['provider_category_id'])) {

                 // Category filters
                $provider_categories = ProviderCategory::select('id', 'categoryL3', 'categoryL4')
                    ->whereNotNull('categoryL3')
                    ->where('categoryL3', '<>', '')
                    ->whereNotNull('categoryL4')
                    ->where('categoryL4', '<>', '')
                    ->where('enabled', 1)
                    ->groupBy('id')
                    ->groupBy('categoryL3')
                    ->groupBy('categoryL4')
                    ->get();

                foreach ($provider_categories as $provider_category)
                    $this->buildProviderFilterCategory($provider_filters, $provider_category, 'SELECT');

                if (isset($params['provider_attribute_value_id']))
                    unset($params['provider_attribute_value_id']);
            }
            elseif (!isset($params['provider_attribute_value_id'])) {
                // Selected remove filter: Category
                $provider_category = ProviderCategory::find($params['provider_category_id']);
                $this->buildProviderFilterCategory($provider_filters, $provider_category, 'DELETE');
                $provider_attributes = $this->getProviderAttributesBycategory($params['provider_category_id'], null);
                foreach ($provider_attributes as $provider_attribute)
                    $this->buildProviderFilterAttribute($provider_filters, $provider_attribute, 'SELECT');
            }
            else {
                // Selected remove filter: Category
                $provider_category = ProviderCategory::find($params['provider_category_id']);
                $this->buildProviderFilterCategory($provider_filters, $provider_category, 'DELETE');

                // Selected remove filter: Atributes
                $provider_attribute_value_ids = explode(',', $params['provider_attribute_value_id']);
                $provider_attributes_selected = ProviderAttribute::select(
                    'provider_attributes.id as provider_attribute_id',
                    'provider_attributes.name as provider_attribute_name',
                    'provider_attribute_values.name as provider_attribute_value_name',
                    'provider_attribute_values.id as provider_attribute_value_id')
                    ->leftJoin('provider_attribute_values', 'provider_attribute_values.provider_attribute_id', '=', 'provider_attributes.id')
                    ->whereIn('provider_attribute_values.id', $provider_attribute_value_ids)
                    ->get();

                foreach ($provider_attributes_selected as $provider_attribute_selected)
                    $this->buildProviderFilterAttribute($provider_filters, $provider_attribute_selected, 'DELETE');

                // Atribute filters
                $provider_attributes_selected_ids = $provider_attributes_selected->pluck('provider_attribute_id')->toArray();
                $provider_attributes = $this->getProviderAttributesBycategory($params['provider_category_id'], $provider_attributes_selected_ids);
                foreach ($provider_attributes as $provider_attribute)
                    $this->buildProviderFilterAttribute($provider_filters, $provider_attribute, 'SELECT');
            }

            return $provider_filters;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $params);
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



    public function indexImages(Request $request)
    {
        $statuses = Status::where('type', 'product')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        //$params = $request->except('_token');
        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'products.created_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $products = Product::filter($params)->paginate(50);     //all()->take(50);

        return view('product.index_images', compact('products', 'statuses', 'suppliers', 'params', 'order_params'));
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $user = Auth::user();
        if ($user->hasRole('seller'))
            $suppliers = Supplier::orderBy('name', 'asc')->find($user->getSuppliersId());
        else
            $suppliers = Supplier::orderBy('name', 'asc')->get();

        //$suppliers = Supplier::orderBy('name', 'asc')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name', 'desc')->get();
        $statuses = Status::where('type', 'product')->orderBy('name', 'asc')->get();
        $types = Type::where('type', 'product')->orderBy('name', 'asc')->get();
        $currencies = Currency::orderBy('name', 'asc')->get();

        return view('product.create-edit', compact('suppliers', 'brands', 'categories', 'statuses', 'types', 'currencies'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'product_id'    => 'nullable|exists:products,id',       // parent_id
            'brand_id'      => 'nullable|exists:brands,id',
            'brand_name'    => 'nullable|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'category_name' => 'nullable|max:255',
            'status_id'     => 'required|exists:statuses,id',
            'currency_id'   => 'required|exists:currencies,id',

            'item_select'       => 'nullable|max:255',
            'item_reference'    => 'nullable|max:255',

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

        $parent = null;
        if (!isset($validatedData['item_reference'])) $validatedData['product_id'] = null;
        if (isset($validatedData['item_reference']) && $validatedData['item_reference'] != null) {
            if ($validatedData['item_select'] == 'pn')
                $parent = Product::where('pn', $validatedData['pn'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $parent = Product::where('ean', $validatedData['ean'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $parent = Product::where('upc', $validatedData['upc'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $parent = Product::where('isbn', $validatedData['isbn'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $parent = Product::where('gtin', $validatedData['gtin'])->first();
            elseif ($validatedData['item_select'] == 'name')
                if (isset($validatedData['product_id']) && $validatedData['product_id'] != null)
                    $parent = Product::find($validatedData['product_id']);
                else
                    $parent = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
        }

        if ($parent)
            $validatedData['parent_id'] = $parent->id;
        elseif (isset($validatedData['product_id']) && $validatedData['product_id'] != null)
            $validatedData['parent_id'] = $validatedData['product_id'];

        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);
        unset($validatedData['product_id']);

        $validatedData['ready'] = $request->has('ready') ? 1 : 0;
        $validatedData['fix_text'] = $request->has('fix_text') ? 1 : 0;

        if ($validatedData['brand_name']) {
            $brand = Brand::firstOrCreate(
                [
                    'name' => $validatedData['brand_name']
                ],[]);
            $validatedData['brand_id'] = $brand->id;
        }
        unset($validatedData['brand_name']);
        unset($validatedData['category_name']);

        Product::create($validatedData);

        return redirect()->route('products.index')->with('status', 'Producto creado correctamente.');
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Product $product)
    {
        return view('product.show', compact('product'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $user = Auth::user();
        if ($user->hasRole('seller'))
            $suppliers = Supplier::orderBy('name', 'asc')->find($user->getSuppliersId());
        else
            $suppliers = Supplier::orderBy('name', 'asc')->get();

        //$suppliers = Supplier::orderBy('name', 'asc')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name', 'desc')->get();
        $statuses = Status::where('type', 'product')->orderBy('name', 'asc')->get();
        $types = Type::where('type', 'product')->orderBy('name', 'asc')->get();
        $currencies = Currency::orderBy('name', 'asc')->get();

        return view('product.create-edit', compact('product', 'suppliers', 'brands', 'categories', 'statuses', 'types', 'currencies'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'product_id'    => 'nullable|exists:products,id',       // parent_id
            'brand_id'      => 'nullable|exists:brands,id',
            'brand_name'    => 'nullable|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'category_name' => 'nullable|max:255',
            'status_id'     => 'required|exists:statuses,id',
            'currency_id'   => 'required|exists:currencies,id',

            'item_select'       => 'nullable|max:255',
            'item_reference'    => 'nullable|max:255',

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

        $parent = null;
        if (!isset($validatedData['item_reference'])) $validatedData['product_id'] = null;
        if (isset($validatedData['item_reference']) && $validatedData['item_reference'] != null) {
            if ($validatedData['item_select'] == 'pn')
                $parent = Product::where('pn', $validatedData['pn'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $parent = Product::where('ean', $validatedData['ean'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $parent = Product::where('upc', $validatedData['upc'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $parent = Product::where('isbn', $validatedData['isbn'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $parent = Product::where('gtin', $validatedData['gtin'])->first();
            elseif ($validatedData['item_select'] == 'name')
                if (isset($validatedData['product_id']) && $validatedData['product_id'] != null)
                    $parent = Product::find($validatedData['product_id']);
                else
                    $parent = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
        }

        if ($parent)
            $validatedData['parent_id'] = $parent->id;
        elseif (isset($validatedData['product_id']) && $validatedData['product_id'] != null)
            $validatedData['parent_id'] = $validatedData['product_id'];

        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);
        unset($validatedData['product_id']);

        $validatedData['ready'] = $request->has('ready') ? 1 : 0;
        $validatedData['fix_text'] = $request->has('fix_text') ? 1 : 0;
        if ($validatedData['brand_name']) {
            $brand = Brand::firstOrCreate(
                [
                    'name' => $validatedData['brand_name']
                ],[]);
            $validatedData['brand_id'] = $brand->id;
        }

        unset($validatedData['brand_name']);
        unset($validatedData['category_name']);

        // CHANGED CATEGORY_ID: Change product_attributes->attributes->category
        if (($product->category_id != $validatedData['category_id']) && ($product->has('product_attributes'))) {
            foreach ($product->product_attributes as $product_attribute) {
                $attribute = $product_attribute->attribute;
                $new_attribute = Attribute::firstOrCreate(
                    [
                        'category_id'   => $validatedData['category_id'],
                        'name'          => $attribute->name
                    ],
                    []
                );
                $product_attribute->attribute_id = $new_attribute->id;
                $product_attribute->save();
            }
        }

        Product::whereId($product->id)->update($validatedData);

        return redirect()->route('products.show', compact('product'))->with('status', 'Producto modificado correctamente.');
    }


    public function destroy(Product $product)
    {
        try {
            if ($product->deleteSecure())
                return redirect()->route('products.index')->with('status', 'Producto eliminado.');

            return redirect()->route('products.index')->with('status', 'No se ha podido eliminar el prodducto. Revisa los filtros, parámetros y ofertas de tiendas.');

        } catch (QueryException $e) {
            return redirect()->route('products.index')->with('status', $e->getMessage());
        }
    }


    // PRODUCT IMAGES

    public function images(Product $product)
    {
        $images = $product->images()->orderBy('type')->get();

        return view('product.images', compact('product', 'images'));
    }


    public function storeImages(Request $request, Product $product)
    {
        request()->validate([
            //'image' => 'required',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:4096'
        ]);

        $check = true;

        // Delete checked images
        if ($request->delete) {
            $images_keys_delete = array_keys($request->delete);
            $images = Image::whereIn('id', $images_keys_delete)->pluck('src')->toArray();
            $prefixed_images = preg_filter('/^/', 'public/img/' . $product->id . '/', $images);
            $check = Image::destroy($images_keys_delete);
            if ($check) $check = Storage::delete($prefixed_images);
            //if (($check) && ($request->has('delete-all')) ) REMOVE DIRECTORY
        }

        // Add new images
        if ($images = $request->file('image')) {
            $res = [];
            foreach ($images as $image) {
                // UploadedFile $image
                //$image = FacadesImage::make($image)->resize(300, 200);
                /* $img = Image::make('public/foo.jpg');
                $img->resize(320, 240);
                $img->save('public/bar.jpg'); */

                $res[] = $product->updateOrStoreImage($image);
            }
        }

        $status = $check ? 'Imágenes guardadas correctamente.' : 'Ocurrió un error al guardar las imágenes.';
        return back()->with('status', $status);
    }


    public function orderImages(Request $request, Product $product)
    {
        $order = false;
        if ($product->images->count()) {

            // Order from 0 to n
            $dir = ('public/img/' . $product->id . '/');
            foreach ($product->images as $key => $image) {

                $image = $product->images->get($key);
                $path = storage_path('app/'.$dir);
                if ($exist = File::glob($path.strval($key).'.*')) {

                    // Rename image
                    if ($key != $image->type) {
                        $order = true;
                        $ext = pathinfo($exist[0], PATHINFO_EXTENSION);
                        $new_filename = strval($key). '.' .$ext;
                        $image->type = $key;
                        $image->src = $new_filename;
                        $image->save();
                    }
                } else {

                    // ReOrder image
                    $order = true;
                    $ext = pathinfo($image->src, PATHINFO_EXTENSION);
                    $new_filename = strval($key). '.' .$ext;
                    Storage::move($dir.$image->src, $dir.$new_filename);
                    $image->type = $key;
                    $image->src = $new_filename;
                    $image->save();
                }
            }

            /* foreach ($product->images as $image) {

                // ReOrder image
                if ($count != $image->type) {
                    $order = true;
                    $ext = pathinfo(storage_path($dir.$image->src), PATHINFO_EXTENSION);
                    $new_filename = strval($count). '.' .$ext;
                    Storage::move($dir.$image->src, $dir.$new_filename);
                    $image->type = $count;
                    $image->src = $new_filename;
                    $image->save();
                }

                $count++;
            } */
        }

        $status = $order ? 'Imágenes ordenadas correctamente.' : 'No es necesario ordenar las imágenes.';
        return back()->with('status', $status);
    }


    // PRODUCT ATTRIBUTES

    /* public function attributes(Product $product)
    {
        if (!$product->product_attributes)
            return redirect()->route('products.index')->with('status', 'Este producto no tiene atributos.');

        $product_attributes_count = $product->product_attributes()->count();
        $product_attributes = $product->product_attributes()->paginate(100);

        return view('product.attributes', compact('product', 'product_attributes_count', 'product_attributes'));
    }


    public function destroyAttributes(Product $product, ProductAttribute $product_attribute)
    {
        try {
            $product_attribute->delete();
        } catch (QueryException $e) {
            return redirect()->route('products.attributes', [$product])->with('status', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('products.attributes', [$product])->with('status', $e->getMessage());
        }

        return redirect()->route('products.attributes', [$product])->with('status', 'Atributo de producto eliminado correctamente.');
    } */


    // PRODUCT MARKETS

    public function shops(Product $product)
    {
        $shop_products_count = $product->shop_products()->count();
        $shop_products = $product->shop_products;

        return view('product.shops', compact('product', 'shop_products_count', 'shop_products'));
    }



    // RELATED PRODUCTS

    public function relateds(Product $product)
    {
        $relateds = $product->relateds()->paginate(10);

        return view('product.relateds', compact('product', 'relateds'));
    }


    public function storeRelated(Request $request, Product $product)
    {
        $validatedData = request()->validate([
            'product_id'            => 'nullable|exists:products,id',
            'pn'                    => 'nullable',
            'ean'                   => 'nullable',
            'upc'                   => 'nullable',
            'isbn'                  => 'nullable',
            'supplierSku'           => 'nullable',
            'MPSSku'                => 'nullable',
        ]);

        if (!$validatedData['product_id']) {
            $product = null;
            if ($validatedData['pn'])
                $product = Product::where('pn', $validatedData['pn'])->first();
            if ($validatedData['ean'])
                $product = Product::where('ean', $validatedData['ean'])->first();
            if ($validatedData['upc'])
                $product = Product::where('upc', $validatedData['upc'])->first();
            if ($validatedData['isbn'])
                $product = Product::where('isbn', $validatedData['isbn'])->first();
            if ($validatedData['gtin'])
                $product = Product::where('gtin', $validatedData['gtin'])->first();
            if ($validatedData['supplierSku'])
                $product = Product::where('supplierSku', $validatedData['supplierSku'])->first();
            if ($validatedData['MPSSku'])
                $product = Product::where('id', $this->getIdFromMPSSku($validatedData['MPSSku']))->first();

            if ($product)
                $validatedData['product_id'] = $product->id;
        }

        $product->relateds()->attach($validatedData['product_id']);

        return redirect()->route('products.relateds', [$product])->with('status', 'Relacionado añadido correctamente.');
    }


    public function destroyRelated(Product $product, Product $related)
    {
        try {
            $product->relateds()->detach($related);
        } catch (QueryException $e) {
            return redirect()->route('products.relateds')->with('status', $e->getMessage());
        }

        return redirect()->route('products.relateds', [$product])->with('status', 'Relacionado eliminado correctamente.');
    }


    public function addToShop(Product $product)
    {
        $user = Auth::user();
        if ($user->hasRole('seller')) $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($user->getShopsId());
        else $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();

        return view('product.addtoshop', compact('product', 'shops'));
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

        return redirect()->route('products.index')->with('status', 'Filtro añadido a la Tienda.');
    }



    /*** SCRAPERS */


    public function scrape(Product $product)
    {
        $user = Auth::user();
        if ($user->hasRole('seller')) $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($user->getShopsId());
        else $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();

        return view('product.scrape', compact('product', 'shops'));
    }


    public function scrapeBy(Product $product, $scrapeClass)
    {
        // Scrape By KYNIO, VOX66, ...
        $ean = $product->ean;
        $fullClass = 'App\Scrapes\\' .$scrapeClass;
        $scraper = new $fullClass($product);
        $res = $scraper->scrape();

        return redirect()->route('products.show', compact('product'));
    }





}
