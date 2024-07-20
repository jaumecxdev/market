<?php

namespace App;

use App\Events\ProductUpdatingEvent;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as FacadesImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * App\Product
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $supplier_id
 * @property int|null $brand_id
 * @property int|null $category_id
 * @property int|null $type_id
 * @property int|null $status_id
 * @property int $ready
 * @property string|null $name
 * @property string|null $keys
 * @property string|null $model
 * @property string|null $supplierSku
 * @property string|null $pn
 * @property string|null $ean
 * @property string|null $upc
 * @property string|null $isbn
 * @property float $cost
 * @property int $stock
 * @property string|null $short
 * @property string|null $long
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Status|null $Status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Attribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Brand|null $brand
 * @property-read \App\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $childs
 * @property-read int|null $childs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Image[] $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Price[] $log_prices
 * @property-read int|null $log_prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketFee[] $market_fees
 * @property-read int|null $market_fees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $order_items
 * @property-read int|null $order_items_count
 * @property-read \App\Product $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductAttribute[] $product_attributes
 * @property-read int|null $product_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $relateds
 * @property-read int|null $relateds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $shop_products
 * @property-read int|null $shop_products_count
 * @property-read \App\ShopProduct|null $shop_product
 * @property-read \App\Supplier|null $supplier
 * @property-read \App\Type|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereEan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereIsbn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereLong($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product wherePn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereReady($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereSupplierSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereUpc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Currency $Currency
 * @property int|null $currency_id
 * @property float $tax
 * @property string|null $size
 * @property string|null $color
 * @property string|null $material
 * @property string|null $style
 * @property string|null $gender
 * @property string|null $weight
 * @property string|null $length
 * @property string|null $width
 * @property string|null $height
 * @property string|null $gtin
 * @property-read \App\Currency|null $currency
 * @property-read mixed $public_url_sku_image
 * @property-read mixed $url_sku_image
 * @property-read \App\Status|null $status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereGtin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereMaterial($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereWidth($value)
 * @property-read mixed $public_url_images
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierParam[] $supplier_params
 * @property-read int|null $supplier_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Price[] $prices
 * @property-read int|null $prices_count
 * @property string|null $keywords
 * @property string|null $shortdesc
 * @property string|null $longdesc
 * @property int $fix_text
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereFixText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereLongdesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Product whereShortdesc($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FeeParam[] $fee_params
 * @property-read int|null $fee_params_count
 * @property-read mixed $storage_path_images
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopParam[] $shop_params
 * @property-read int|null $shop_params_count
 * @property-read \App\Provider $provider
 * @property int|null $provider_id
 * @method static Builder|Product whereProviderId($value)
 * @property int|null $supplier_category_id
 * @property int|null $provider_category_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $lasteds_shop_products
 * @property-read int|null $lasteds_shop_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Promo[] $promos
 * @property-read int|null $promos_count
 * @property-read \App\ProviderCategory|null $provider_category
 * @property-read \App\SupplierCategory|null $supplier_category
 * @method static Builder|Product whereProviderCategoryId($value)
 * @method static Builder|Product whereSupplierCategoryId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProviderProductAttribute[] $provider_product_attributes
 * @property-read int|null $provider_product_attributes_count
 */
class Product extends Model
{
    use HelperTrait;

    protected $table = 'products';

    // fix_text: NOT allow UPDATE the name, keywords, shortdesc, longdesc -> EVENT: ProductUpdatingEvent
    protected $fillable = [
        'ready', 'supplier_id', 'parent_id', 'supplier_brand_id', 'brand_id', 'supplier_category_id', 'category_id', 'type_id', 'status_id', 'currency_id',
        'provider_id', 'provider_category_id',
        'name', 'keywords', 'pn', 'ean', 'upc', 'isbn', 'gtin', 'shortdesc', 'longdesc', 'weight', 'length', 'width', 'height',
        'supplierSku', 'model', 'cost', 'tax', 'stock', 'size', 'color', 'material', 'style', 'gender',
        'fix_text'
    ];

    protected $dispatchesEvents = [
        'updating' => ProductUpdatingEvent::class,
    ];


    const SKU_FIELDS = ['size', 'color', 'material', 'style', 'gender'];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function parent()
    {
        return $this->belongsTo('App\Product', 'parent_id', 'id');
    }

    public function supplier_brand()
    {
        return $this->belongsTo('App\SupplierBrand');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function supplier_category()
    {
        return $this->belongsTo('App\SupplierCategory');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function type()
    {
        return $this->belongsTo('App\Type');
    }

    public function status()
    {
        return $this->belongsTo('App\Status');
    }

    public function currency()
    {
        return $this->belongsTo('App\Currency');
    }

    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }

    public function provider_category()
    {
        return $this->belongsTo('App\ProviderCategory');
    }



    // MANY

    public function childs()
    {
        return $this->hasMany('App\Product', 'parent_id', 'id');
    }

    public function images()
    {
        return $this->hasMany('App\Image');
    }

    public function prices()
    {
        return $this->hasMany('App\Price');
    }

    public function promos()
    {
        return $this->hasMany('App\Promo');
    }

    public function order_items()
    {
        return $this->hasMany('App\OrderItem');
    }

    public function product_attributes()
    {
        return $this->hasMany('App\ProductAttribute');
    }

    public function provider_product_attributes()
    {
        return $this->hasMany('App\ProviderProductAttribute');
    }

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function shop_params()
    {
        return $this->hasMany('App\ShopParam');
    }

    public function shop_products()
    {
        return $this->hasMany('App\ShopProduct');
    }

    public function lasteds_shop_products()
    {
        return $this->hasMany('App\ShopProduct', 'last_product_id', 'id');
    }


    // MANY TO MANY

    public function relateds()
    {
        return $this->belongsToMany('App\Product', 'product_product', 'product_id', 'related_id');
    }

    public function attributes()
    {
        return $this->belongsToMany('App\Attribute', 'product_attributes',
            'product_id', 'attribute_id');
    }



    //  CUSTOM METHODS


    public function shop_product($shop_id)
    {
        return $this->hasOne('App\ShopProduct')->where('shop_id', $shop_id);
    }


    public function setCostAttribute($value)
    {
        try {
            $this->attributes['cost'] = number_format((float)round($value, 2), 2, '.', '');

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$value, $this]);
        }
    }


    public function getEanAttribute($value)
    {
        try {
            if (!isset($value)) return null;

            return
                str_replace(
                    ['(', ')', ' ', chr(194).chr(160)],
                    '',
                    stripslashes(trim(mb_convert_encoding($value, 'UTF-8'))));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $value);
        }
    }


    // TODO: Accessor getMpsSkuAttribute()
    public function getMPSSku($length = 128)
    {
        try {
            return mb_substr(
                str_replace(
                    ['(', ')', ' ', chr(194).chr(160), '/', '\\', '.'],
                    '',
                    stripslashes(trim(mb_convert_encoding(strval($this->id). '_' .$this->pn. '_' .$this->ean, 'UTF-8'))))
                , 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $this]);
        }
    }


    public function hasSkuInfo()
    {
        // 'size', 'color', 'material'
        return ($this->size != null || $this->color != null || $this->material != null || $this->style != null || $this->gender != null);
    }


    public function isSkuField($field)
    {
        try {
            return in_array($field, self::SKU_FIELDS);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$field, $this]);
        }
    }


    public function getSkuFields()
    {
        try {
            $sku_fields = [];
            foreach (self::SKU_FIELDS as $sku_field)
                if (isset($this->$sku_field)) $sku_fields[$sku_field] = $this->$sku_field;

            return $sku_fields;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function logPrice($new = true)
    {
        try {
            Price::updateOrcreate([
                'product_id'    => $this->id,
                'name'          => $new ? 'New Supplier Product' : 'Update Supplier Product',
                'cost'          => $this->cost,
                'stock'         => $this->stock,
            ],[]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$new, $this]);
        }
    }


    public function getNextImageType()
    {
        try {
            $type = 0;
            $exit = false;
            $current_types = $this->images()->pluck('type')->toArray();
            while (!$exit && $type < 1000) {
                if (!in_array($type, $current_types))
                    $exit = true;
                else
                    $type++;
            }

            return $type;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this->id);
        }
    }


    public function updateCostStock($supplierSku, $cost, $tax, $currency_id, $stock,
        $brand_id, $supplier_category_id, $category_id, $status_id)
    {
        try {
            return $this->update([
                'supplierSku'           => $supplierSku,
                'cost'                  => $cost,
                'tax'                   => $tax,
                'currency_id'           => $currency_id ?? $this->$currency_id,
                'stock'                 => $stock,

                'brand_id'              => $brand_id ?? $this->brand_id,
                'supplier_category_id'  => $supplier_category_id ?? $this->supplier_category_id,
                'category_id'           => $category_id ?? $this->category_id,
                'status_id'             => $status_id ?? $this->$status_id,
            ]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$supplierSku, $cost, $tax, $currency_id, $stock,
                $brand_id, $supplier_category_id, $category_id, $status_id, $this]);
        }
    }



    public function updateOrStoreImage(UploadedFile $image, $type = null)
    {
        try {
            // SKU Image: Type = 0
            // Front Image: Type = 1
            if (!isset($type)) $type = $this->getNextImageType();

            $directory = ('public/img/' . $this->id . '/');         // upload path
            $ext = $image->getClientOriginalExtension();
            $imageName = strval($type). '.' .$ext;

            //$image->storeAs($directory, $imageName);
            //Storage::putFileAs($directory, $image, $imageName);

            $iimage = FacadesImage::make($image)->encode($ext);
            // > 0.5MB ?
            if ($iimage->filesize() > 524288) {
                $width = 800;
                if ($iimage->width() < 800) $width = intdiv($iimage->width(), 2);
                $iimage->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode($ext);
            };
            Storage::put($directory.$imageName, $iimage->__toString());
            Image::updateOrCreate(
                [
                    'product_id'    => $this->id,
                    'src'           => $imageName,
                    'type'          => $type,
                ]
            );

            return ($type. ': ' .$imageName);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$image, $type, $this->id]);
        }
    }


    private function storageGetByGuzzle($file)
    {
        try {
            $contents = null;
            $client = new Client();
            $response = $client->get($file, [
                'headers' => [
                    //'Content-Type'  => 'text/xml',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36'
                ],
            ]);

            if ($response->getStatusCode() == '200')
                $contents = $response->getBody()->getContents();

            return $contents;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$file, $this]);
        }
    }


    public function updateOrCreateExternalImage($image, $type = null, $disk = null)
    {
        try {
            if (!isset($type)) $type = $this->getNextImageType();

            $directory = ('public/img/' . $this->id . '/');         // upload path
            $ext = pathinfo($image, PATHINFO_EXTENSION);
            $imageName = strval($type). '.' .$ext;

            // By FTP || URL
            // Space = %20
            //$contents = isset($disk) ? Storage::disk($disk)->get($image) : file_get_contents($image);
            /* $contents = null;
            if (isset($disk)) $contents = Storage::disk($disk)->get($image);
            else {
                $client = new Client();     //['base_uri' => $shop->endpoint]);
                $response = $client->get($image, [
                    //'Accept' => 'image/jpeg',
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36'
                    ]]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                }
            } */

            //if (isset($contents)) {
            // Create directory, If necessary
            Storage::makeDirectory($directory);
            // Remove & update image file
            if (Storage::exists($directory . $imageName)) {
                Storage::delete($directory . $imageName);
            }

            //$contents = isset($disk) ? Storage::disk($disk)->get($image) : $this->storageGetByGuzzle($image);       //file_get_contents($image);
            $contents = isset($disk) ? Storage::disk($disk)->get($image) : file_get_contents($image);
            //$contents = new UploadedFile($image, $imageName);


            if (isset($contents)) {
                $iimage = FacadesImage::make($contents)->encode($ext);
                // > 0.5MB ?
                if ($iimage->filesize() > 524288) {
                    $width = 800;
                    if ($iimage->width < 800) $width = intdiv($iimage->$width, 2);
                    $iimage->resize($width, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode($ext);
                };
                Storage::put($directory.$imageName, $iimage->__toString());
                //Storage::put($directory . $imageName, $contents);
                $imagee = Image::updateOrCreate(
                    [
                        'product_id'    => $this->id,
                        'src'           => $imageName,
                        'type'          => $type,
                    ]
                );
            }

            return $imageName;
        }
        catch (Throwable $th) {
            Storage::append('images/' .date('Y-m-d'). '_updateOrCreateExternalImage_errors.json', json_encode([$image, $type, $disk, $this->id]));
            return null;
            //return $this->nullWithErrors($th, __METHOD__, [$image, $type, $disk, $this->id]);
        }
    }


    public function updateOrCreateExternalImages($images_data, $disk = null)
    {
        try {
            $images_names = null;
            $type = $this->getNextImageType();
            $images = isset($disk) ? Storage::disk($disk)->files($images_data) : $images_data;

            foreach($images as $image) {

                $images_names[] = $this->updateOrCreateExternalImage($image, $type, $disk);
                $type++;
            }

            return $images_names;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$images_data, $disk, $this]);
        }
    }


    // OLD -> getMPEProductImages()
    public function copyMPEProductImages()
    {
        try {
            // Get images from MPS Products
            $product_with_images = FacadesMpe::getMPEProductWithImages($this->pn, $this->brand_id, $this->ean);
            if (isset($product_with_images)) {
                $type = 0;
                $directory_source = ('public/img/' . $product_with_images->id . '/');
                $directory_destination = ('public/img/' . $this->id . '/');
                Storage::makeDirectory($directory_destination);
                foreach($product_with_images->images as $image) {
                    Storage::copy($directory_source.$image->src, $directory_destination.$image->src);
                    Image::updateOrCreate(
                        [
                            'product_id'    => $this->id,
                            'src'           => $image->src,
                            'type'          => $type,
                        ]
                    );
                    $type++;
                }
                return true;
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getMPESimilarProductWithImages()
    {
        if (isset($this->ean) && $this->ean != '')
            return Product::whereHas('images')
                //->whereNotNull('products.ean')
                ->where('products.ean', $this->ean)
                ->where('products.id', '<>', $this->id)
                ->first();
        elseif (isset($this->pn) && $this->pn != '' && isset($this->brand_id))
            return Product::whereHas('images')
                //->whereNotNull('products.pn')
                //->whereNotNull('products.brand_id')
                ->where('products.pn', $this->pn)
                ->where('products.brand_id', $this->brand_id)
                ->where('products.id', '<>', $this->id)
                ->first();

        return null;
    }


    public function getMPEProductImages()
    {
        try {
            // Get images from other MPE Product with same (BRAND && PN) || EAN
            // OLD: FacadesMpe::getMPEProductWithImages($this->pn, $this->brand_id, $this->ean))
            if ($product_with_images = $this->getMPESimilarProductWithImages())
                return $this->copyImages($product_with_images);

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getSimilarProducts($without_provider = false)
    {
        try {
            $query = null;
            if (isset($this->ean) && $this->ean != '') {
                $query = Product::where('products.id', '<>', $this->id)
                    //->where('status_id', $status_id)
                    ->where('ean', $this->ean);
                    //->where('ready', 1)
                    //->get();
            } elseif (isset($this->pn) && $this->pn != '' && isset($this->brand_id)) {
                $query = Product::where('products.id', '<>', $this->id)
                    //->where('status_id', $status_id)
                    ->where('pn', $this->pn)
                    ->where('brand_id', $this->brand_id);
                    //->where('ready', 1)
                    //->get();
            }

            if ($without_provider)
                $query->whereNull('products.provider_id');

            if ($query)
                return $query->get();

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getMPEProductEan()
    {
        try {
            if (isset($this->pn)
                && $this->pn != ''
                && isset($this->brand_id)
                && !isset($this->ean) && $product = Product::whereNotNull('ean')
                    ->wherePn($this->pn)
                    ->whereBrandId($this->brand_id)
                    ->first()) {

                $this->ean = $product->ean;
                $this->save();

                return true;
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    // Copy images FROM: $product_with_images TO $this
    public function copyImages(Product $product_with_images)
    {
        try {
            if ($product_with_images->images->count()) {

                $type = 0;
                $source_dir = ('public/img/' . $product_with_images->id . '/');
                $destitation_dir = ('public/img/' . $this->id . '/');
                Storage::makeDirectory($destitation_dir);

                foreach($product_with_images->images as $image) {
                    if (Storage::exists($destitation_dir.$image->src))
                        Storage::delete($destitation_dir.$image->src);

                    Storage::copy($source_dir.$image->src, $destitation_dir.$image->src);
                    Image::updateOrCreate(
                        [
                            'product_id'    => $this->id,
                            'src'           => $image->src,
                            'type'          => $type,
                        ]
                    );
                    $type++;
                }
                return $type;
            }

            return false;

        } catch (Throwable $th) {
            // Image NO Exist -> Delete $product_with_images image relation
            // "File not found at path: public/img/73761/0.jpg",
            $err_msg = $th->getMessage();
            $product_id_str = (string)($product_with_images->id);
            if (strpos($err_msg, $product_id_str)) {
                $image_src = mb_substr($err_msg, 36 + strlen($product_id_str));
                Image::whereProductId($product_with_images->id)->whereSrc($image_src)->delete();

                return $this->nullAndStorage(__METHOD__, ['IMAGE PRODUCT DELETED SUCCESSFULLY: '.$image_src, 'PRODUCT_WITH_IMAGES_ID: '.$product_with_images->id, 'PRODUCT_ID: '.$this->id]);
            }

            return $this->nullWithErrors($th, __METHOD__, [$product_with_images, $this]);
        }
    }


    public function deleteAllImages()
    {
        try {
            $this->images()->delete();
            $this->save();

            $images_dir = 'public/img/' .$this->id. '/';
            if (Storage::exists($images_dir))
                Storage::deleteDirectory($images_dir);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function deleteSecure()
    {
        try {
            if (!$this->shop_products->count() &&
                !$this->order_items->count() &&
                !$this->shop_filters->count() &&
                !$this->shop_params->count() &&
                !$this->lasteds_shop_products->count()) {

                if ($this->childs->count()) $this->childs()->delete();
                if ($this->prices->count()) $this->prices()->delete();
                if ($this->promos->count()) $this->promos()->delete();
                if ($this->product_attributes->count()) $this->product_attributes()->delete();
                if ($this->provider_product_attributes->count()) $this->provider_product_attributes()->delete();
                if ($this->relateds->count()) $this->relateds()->delete();
                if ($this->childs->count()) $this->childs()->delete();

                if ($this->images->count()) $this->deleteAllImages();

                // REMOVE DDBB PRO PRODUCTS
                $buyer_products = DB::connection('mysql_pro')->table('buyer_products')
                    ->where('product_id', $this->id)->get();
                if ($buyer_products->count()) {

                    foreach ($buyer_products as $buyer_product) {
                        DB::connection('mysql_pro')->table('store_group_filters')
                            ->where('buyer_product_id', $buyer_product->id)->delete();

                        DB::connection('mysql_pro')->table('store_group_params')
                            ->where('buyer_product_id', $buyer_product->id)->delete();

                        DB::connection('mysql_pro')->table('store_group_products')
                            ->where('buyer_product_id', $buyer_product->id)->delete();

                        DB::connection('mysql_pro')->table('buyer_products')
                            ->where('id', $buyer_product->id)->delete();
                    }
                }

                DB::connection('mysql_pro')->table('store_group_products')
                    ->where('product_id', $this->id)->delete();

                DB::connection('mysql_pro')->table('filters')
                    ->where('product_id', $this->id)->delete();

                DB::connection('mysql_pro')->table('params')
                    ->where('product_id', $this->id)->delete();



                $this->delete();
                return true;
            }

            return false;

        } catch (Throwable $th) {
            $this->ready = 0;
            $this->save();
            return $this->nullWithErrors($th, __METHOD__, $this);
        };
    }


    public function image($type = 1)
    {
        return $this->hasOne('App\Image')->where('type', $type);
    }


    public function url_image($type = 1)
    {
        try {
            $image = $this->image($type)->first();

            return ($image ? Storage::url('img/' .$this->id. '/' .$image->src) : null);
    //        if (!$this->image($type)->first()) return null;
    //        return Storage::url('img/' .$this->id. '/' .$this->image($type)->first()->src);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$type, $this]);
        }
    }


    public function public_url_image($type = 1)
    {
        try {
            return secure_url($this->url_image($type));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$type, $this]);
        }
    }


    // Accessor: url_sku_image
    public function getUrlSkuImageAttribute()
    {
        try {
            return $this->url_image(0);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    // Accessor: public_url_sku_image
    public function getPublicUrlSkuImageAttribute()
    {
        try {
            return secure_url($this->url_image(0));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function url_images()
    {
        try {
            $srcs = [];
            foreach ($this->images as $image) {
                $srcs[] = Storage::url('img/' .$this->id. '/' .$image->src);
            }

            return $srcs;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    // Accessor: storage_path_images
    public function getStoragePathImagesAttribute()
    {
        try {
            return $this->images()->orderBy('type', 'asc')->pluck('src')
                ->map(function ($item, $key) {
                    return storage_path('app/public/img/' .$this->id. '/' .$item);
                });

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    // Accessor: public_url_images
    public function getPublicUrlImagesAttribute()
    {
        try {
            return $this->images()->orderBy('type', 'asc')->pluck('src')
                ->map(function ($item, $key) {
                    return secure_url(Storage::url('img/' .$this->id. '/' .$item));
                });

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getFirstImageFullUrl()
    {
        try {
            $image_src = Image::where('product_id', $this->id)->value('src');
            if (!$image_src) return '';

            return Storage::url('img/' .$this->id. '/' .$image_src);
            /* return ((Request::getHost() == 'mpespecialist.com') ?
                secure_url(Storage::url('img/' .$this->id. '/' .$image_src)) :
                url(Storage::url('img/' .$this->id. '/' .$image_src))); */

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getAllUrlImages($number = 50)
    {
        try {
            return $this->images()->pluck('src')
                ->map(function ($item, $key) use ($number) {
                    return ($key < $number) ? secure_url(Storage::url('img/' .$this->id. '/' .$item)) : null;
                })->slice(0, $number);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$number, $this]);
        }
    }


    /*** GET PRODUCT TITLE ***/



    /* private function buildTitleOutlet()
    {
        try {
            $title = '';
            if (($this->status) && ($this->status->name == 'Remanufacturado')) {
                $title = "OFERTA CAJA ABIERTA ";
            }

            return $title;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    } */


    /* private function buildTitleSeoName()
    {
        try {
            if (isset($this->category->seo_name)) {
                $title = (isset($this->shortdesc) && (strlen($this->shortdesc) > strlen($this->name)) ) ?
                    (strip_tags($this->shortdesc). ' ' .strip_tags($this->name)) :
                    strip_tags($this->name). ' ' .strip_tags($this->shortdesc);

                return ($this->category->seo_name. ' ' .$title);
            }

            return strip_tags($this->name);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    } */


    public function buildTitle($length = 255)
    {
        try {
            /* $title = (isset($this->shortdesc) && (strlen($this->shortdesc) > strlen($this->name)) ) ?
                    $this->shortdesc : $this->name; */

            return mb_substr(stripslashes(strip_tags($this->name)), 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $this]);
        }
    }


    /*** GET PRODUCT DESCRIPTION ***/


    /* private function buildDescriptionHeader($store_name, $store_url, $header_url)
    {
        try {
            $header_desc = '';
            if (isset($store_url) && isset($header_url)) {
                $header_desc = '<p><a href="' .$store_url. '"><img alt="' .$store_name. '" style="width: 100%"
                    src="' .$header_url. '"></a></p><br>';
            }

            return $header_desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$store_name, $store_url, $header_url, $this]);
        }
    } */


    private function buildDescriptionOutlet4Mobile($new_line_char = "\n")
    {
        try {
            $desc = "";
            $status = $this->status;
            if (($status) && ($status->name == 'Remanufacturado')) {
                $desc .= "Las OFERTAS CAJA ABIERTA son productos devueltos sin usar o bien reacondicionados. Cuando nos llegan, los comprobamos y certificamos. ".
                    "El producto funciona perfectamente, como nuevo. Dispone de un año de garantía del fabricante. ".
                    "Aprovecha y disfruta de un dispositivo a un precio reducido.".$new_line_char.$new_line_char;
            }

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$new_line_char, $this]);
        }
    }


    public function buildDescriptionLong4Excel($length = 3500, $new_line_char = "\n")
    {
        try {
            $desc = "";
            if (isset($this->longdesc)) {
                $desc = $new_line_char.$new_line_char .trim(stripslashes($this->longdesc));
                // Remove anchors
                $desc = preg_replace('/<a.*?a>/', "", $desc);
                // Add Break before TABLEs
                $desc = str_replace('<table', $new_line_char."<table", $desc);
                // Remove Tabs
                $desc = trim(preg_replace('/\t+/', "", $desc));

                // Add : + Space TO attribute name: value
                //$desc = str_replace('</td><td>', ': ', $desc);
                $desc = preg_replace("/<\/td><td(.*?)>/", ": ", $desc);

                // Add \n FOR NEXT attribute
                //$desc = str_replace('</td></tr><tr><td>', "\n", $desc);
                $desc = preg_replace("/<\/td><\/tr><tr(.*?)><td(.*?)>/", $new_line_char, $desc);
                // Replace <br> TO \n
                // CHANGE < -> &lt
                $desc = str_replace(["<br>", "<br />", "<br/>"], $new_line_char, $desc);

                // Remove paragrafs + Add \n
                $desc = preg_replace('/<p.*?>/', "", $desc);
                $desc = str_replace('</p>', $new_line_char, $desc);
                $desc = strip_tags($desc);

                // <br> <b> </b>
                $desc = str_replace(['&lt;br&gt;', '&lt;b&gt;', '&lt;/b&gt;n', 'nn&lt;b&gt;'], '', $desc);
                $desc = str_replace(['- ', '-'], [', ', ', '], $desc);

                $desc = mb_substr($desc, 0, $length-50);
                $desc .= '. PartNumber: ' .$this->pn.', EAN13: ' .$this->ean;
            }

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $new_line_char, $this]);
        }
    }


    private function buildDescriptionLong4Mobile($new_line_char = "\n")
    {
        try {
            $desc = "";
            if (isset($this->longdesc)) {
                $desc = $new_line_char.$new_line_char .stripslashes($this->longdesc);
                // Remove anchors
                $desc = preg_replace('/<a.*?a>/', "", $desc);
                // Add Break before TABLEs
                $desc = str_replace('<table', $new_line_char."<table", $desc);
                // Remove Tabs
                $desc = trim(preg_replace('/\t+/', "", $desc));

                // Add : + Space TO attribute name: value
                //$desc = str_replace('</td><td>', ': ', $desc);
                $desc = preg_replace("/<\/td><td(.*?)>/", ": ", $desc);

                // Add \n FOR NEXT attribute
                //$desc = str_replace('</td></tr><tr><td>', "\n", $desc);
                $desc = preg_replace("/<\/td><\/tr><tr(.*?)><td(.*?)>/", $new_line_char, $desc);
                // Replace <br> TO \n
                // CHANGE < -> &lt
                $desc = str_replace(["<br>", "<br />", "<br/>"], $new_line_char, $desc);

                // Remove paragrafs + Add \n
                $desc = preg_replace('/<p.*?>/', "", $desc);
                $desc = str_replace('</p>', $new_line_char, $desc);
                $desc = strip_tags($desc);

                // <br> <b> </b>
                $desc = str_replace(['&lt;br&gt;', '&lt;b&gt;', '&lt;/b&gt;n', 'nn&lt;b&gt;'], '', $desc);
            }

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$new_line_char, $this]);
        }
    }


    private function buildDescriptionAttributes4Mobile($new_line_char = "\n")
    {
        try {
            // ATRIBUTOS + PART NUMBER + EAN
            $desc = $new_line_char;
            if ($this->product_attributes()->count()) {
                foreach ($this->product_attributes as $product_attribute) {
                    $desc .= $new_line_char .$product_attribute->attribute->name. ": " .$product_attribute->value;
                }
            }
            $desc .= (isset($this->pn)) ? ($new_line_char."Part Number: " .$this->pn) : "";
            $desc .= (isset($this->ean)) ? ($new_line_char."EAN13: " .$this->ean) : "";
            $desc .= (isset($this->upc)) ? ($new_line_char."UPC: " .$this->upc) : "";
            $desc .= (isset($this->isbn)) ? ($new_line_char."ISBN: " .$this->isbn) : "";
            $desc .= (isset($this->gtin)) ? ($new_line_char."GTIN: " .$this->gtin) : "";
            $desc .= (isset($this->model)) ? ($new_line_char."MODEL: " .$this->model) : "";
            $desc .= (isset($this->size)) ? ($new_line_char."SIZE: " .$this->size) : "";
            $desc .= (isset($this->color)) ? ($new_line_char."COLOR: " .$this->color) : "";
            $desc .= (isset($this->material)) ? ($new_line_char."MATERIAL: " .$this->material) : "";
            $desc .= (isset($this->style)) ? ($new_line_char."STYLE: " .$this->style) : "";
            $desc .= (isset($this->gender)) ? ($new_line_char."GENDER: " .$this->gender) : "";
            $desc .= $new_line_char;

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$new_line_char, $this]);
        }
    }


    /* private function buildDescriptionFiles($store_name = '')
    {
        try {
            $desc = '<span>';
            $desc_images = Storage::files('public/files/' .$this->id);
            foreach ($desc_images as $desc_image) {
                //$desc .= '<p><img alt="' .$store_name. '" style="width: 100%" src="' .url(Storage::url($desc_image)). '"></p';
                $desc .= '<p><img alt="' .$store_name. '" style="width:100%" src="' .secure_url(Storage::url($desc_image)). '"></p>';
            }
            $desc .= '</span>';

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$store_name, $this]);
        }
    } */


    private function buildDescriptionImages($store_name = '')
    {
        try {
            $desc = '<span>';
            $desc_images = Storage::files('public/img/' .$this->id);
            foreach ($desc_images as $desc_image) {
                $desc .= '<p><img alt="' .$store_name. '" style="width:100%" src="' .secure_url(Storage::url($desc_image)). '"></p>';
            }
            $desc .= '</span>';

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$store_name, $this]);
        }
    }


    public function buildDescription4Mobile($length = 10000, $new_line_char = "\n")
    {
        try {
            $desc = $this->buildDescriptionOutlet4Mobile();
            $desc .= $this->buildTitle();
            $desc .= $this->buildDescriptionLong4Mobile($new_line_char);
            $desc .= $this->buildDescriptionAttributes4Mobile($new_line_char);

            return mb_substr($desc, 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $new_line_char, $this]);
        }
    }


    public function buildDescription4Html($length = 10000)
    {
        try {
            $desc = str_replace("\n", "<br>", $this->buildDescription4Mobile());
            //$desc .= $this->buildDescriptionFiles();
            $desc .= $this->buildDescriptionImages();

            return mb_substr($desc, 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $this]);
        }
    }


    /* public function buildDescription4BasicHtml($length = 10000)
    {
        try {
            return mb_substr(str_replace("\n", "<br>", $this->buildDescriptionLong4Mobile()), 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $this]);
        }
    } */


    /* public function buildDescription4HtmlWithHeader($store_name, $store_url, $header_url)
    {
        try {
            // EBAY_HEADER_URL:  = 'https://public.mpespecialist.com/img/header.jpg'
            // AE_HEADER_URL: https://public.mpespecialist.com/img/header_locura.jpg
            $desc = $this->buildDescriptionHeader($store_name, $store_url, $header_url);
            $desc .= $this->buildDescription4Html();

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$store_name, $store_url, $header_url, $this]);
        }
    } */


    public function buildKeywords($name, $length = 255)
    {
        try {
            $name = $name ?? $this->name;
            return mb_substr((string)$this->keywords. ' ' .$this->pn. ' ' .$this->ean. ' ' .$name, 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$name, $length, $this]);
        }
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        try {
            $query->select('products.*',
                'suppliers.name as supplier_name',
                'supplier_categories.name as supplier_category_name',
                'supplier_brands.name as supplier_brand_name',
                'brands.name as brand_name',
                'categories.name as category_name',
                'images.src as image_src'
            )
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('supplier_brands', 'products.supplier_brand_id', '=', 'supplier_brands.id')
            ->leftJoin('supplier_categories', 'products.supplier_category_id', '=', 'supplier_categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('images', 'images.id', '=', DB::raw('(SELECT id FROM images WHERE images.product_id = products.id LIMIT 1)'))
            /*->leftJoin('product_attributes', 'products.id', '=', 'product_attributes.product_id')
            ->leftJoin('product_product', 'products.id', '=', 'product_product.product_id')
            ->leftJoin('shop_products', 'products.id', '=', 'shop_products.product_id')*/
            ->groupBy('products.id')
            ->groupBy('images.id');

            //$query->whereNull('images.src');


            if ( isset($params['status_id']) && $params['status_id'] != null) {
                $query->where('products.status_id', $params['status_id']);
            }

            if ( isset($params['supplier_id']) && $params['supplier_id'] != null) {
                $query->where('products.supplier_id', $params['supplier_id']);
            }

            if (!isset($params['brand_name'])) $params['brand_id'] = null;
            if ( isset($params['brand_id']) && $params['brand_id'] != null) {
                $query->where('products.brand_id', $params['brand_id']);
            }

            if (!isset($params['supplier_brand_name'])) $params['supplier_brand_id'] = null;
            if ( isset($params['supplier_brand_id']) && $params['supplier_brand_id'] != null) {
                $query->where('products.supplier_brand_id', $params['supplier_brand_id']);
            }

            if (!isset($params['supplier_category_name'])) $params['supplier_category_id'] = null;
            if ( isset($params['supplier_category_id']) && $params['supplier_category_id'] != null) {
                $query->where('products.supplier_category_id', $params['supplier_category_id']);
            }

            if (!isset($params['category_name'])) $params['category_id'] = null;
            if ( isset($params['category_id']) && $params['category_id'] != null) {
                $query->where('products.category_id', $params['category_id']);
            }

            if ( isset($params['ready']) && $params['ready'] != null) {
                $query->where('products.ready', $params['ready']);
            }

            if ( isset($params['cost_min']) && $params['cost_min'] != null) {
                $query->where('products.cost', '>=', floatval($params['cost_min']));
            }
            if ( isset($params['cost_max']) && $params['cost_max'] != null) {
                $query->where('products.cost', '<=', floatval($params['cost_max']));
            }

            if ( isset($params['stock_min']) && $params['stock_min'] != null) {
                $query->where('products.stock', '>=', intval($params['stock_min']));
            }
            if ( isset($params['stock_max']) && $params['stock_max'] != null) {
                $query->where('products.stock', '<=', intval($params['stock_max']));
            }

            if (!isset($params['item_reference'])) $params['product_id'] = null;
            if (isset($params['item_reference']) && $params['item_reference'] != null) {
                if ($params['item_reference'] == 'null') $params['item_reference'] = null;

                if ($params['item_select'] == 'id')
                    $query->where('products.id', $params['item_reference']);
                elseif ($params['item_select'] == 'supplierSku')
                    $query->where('products.supplierSku', $params['item_reference']);
                elseif ($params['item_select'] == 'pn')
                    $query->where('products.pn', $params['item_reference']);
                elseif ($params['item_select'] == 'ean')
                    $query->where('products.ean', $params['item_reference']);
                elseif ($params['item_select'] == 'upc')
                    $query->where('products.upc', $params['item_reference']);
                elseif ($params['item_select'] == 'isbn')
                    $query->where('products.isbn', $params['item_reference']);
                elseif ($params['item_select'] == 'gtin')
                    $query->where('products.gtin', $params['item_reference']);
                elseif ($params['item_select'] == 'name')
                    if (isset($params['product_id']) && $params['product_id'] != null)
                        $query->where('products.id', $params['product_id']);
                    else
                        $query->where('products.name', 'LIKE', '%' .$params['item_reference']. '%');
            }

            if ( isset($params['supplierSku']) && $params['supplierSku'] != null) {
                if ($params['supplierSku'] == 'null') $params['supplierSku'] = null;
                $query->where('products.supplierSku', $params['supplierSku']);
            }

            if ( isset($params['MPSSku']) && $params['MPSSku'] != null) {
                if ($params['MPSSku'] == 'null') $query->whereNull('products.id');
                else $query->where('products.id', $this->getIdFromMPSSku($params['MPSSku']));
            }



            if ( isset($params['provider_category_id']) && $params['provider_category_id'] != null) {
                $query->where('products.provider_category_id', $params['provider_category_id']);
            }

            if ( isset($params['provider_attribute_value_id']) && $params['provider_attribute_value_id'] != null) {
                //$query->leftJoin('provider_product_attributes', 'provider_product_attributes.product_id', '=', 'products.id');
                $provider_attribute_value_ids = explode(',', $params['provider_attribute_value_id']);

                foreach ($provider_attribute_value_ids as $provider_attribute_value_id) {
                    $query->leftJoin('provider_product_attributes as ppa'.$provider_attribute_value_id, 'ppa'.$provider_attribute_value_id.'.product_id', '=', 'products.id');
                    $query->where('ppa'.$provider_attribute_value_id.'.provider_attribute_value_id', $provider_attribute_value_id);
                }
                /* $query->leftJoin('provider_product_attributes as ppa1', 'ppa1.product_id', '=', 'products.id');
                $query->where('ppa1.provider_attribute_value_id', $provider_attribute_value_ids[0]);
                $query->leftJoin('provider_product_attributes as ppa2', 'ppa2.product_id', '=', 'products.id');
                $query->where('ppa2.provider_attribute_value_id', $provider_attribute_value_ids[1]);
             */
            }

            // ORDER BY
            if ( isset($params['order_by']) && $params['order_by'] != null) {
                $query->orderBy($params['order_by'], $params['order']);
            }

            return $query;

        } catch (Throwable $th) {

            return $this->nullWithErrors($th, __METHOD__, [$query, $params, $this]);
        }
    }

}
