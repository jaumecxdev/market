<?php

namespace App\Console\Commands;

use App\Libraries\SupplierWS;
use App\LogSchedule;
use App\Product;
use App\ProviderProductAttribute;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttributesCopy extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copia los atributos de los productos que ya tienen';

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
            Log::channel('commands')->info('START copy:attributes');
            $log_schedule = LogSchedule::create(['type' => 'copy:attributes', 'name' => 'copy:attributes']);

            $res = [];
            $products_with_provider = Product::whereProviderId(3)->groupBy('products.id')->get();
            foreach ($products_with_provider as $product_with_provider) {

                $similar_products_without_provider = $product_with_provider->getSimilarProducts(true);
                if ($similar_products_without_provider && $similar_products_without_provider->count())
                    foreach ($similar_products_without_provider as $similar_product_without_provider) {

                        // Update: provider_id, provider_category_id
                        $similar_product_without_provider->provider_id = $product_with_provider->provider_id;
                        $similar_product_without_provider->provider_category_id = $product_with_provider->provider_category_id;

                        // Update: name, longdesc, ean, pn, brand_id
                        if (!isset($similar_product_without_provider->name) ||
                            strlen($similar_product_without_provider->name) < strlen($product_with_provider->name))
                            $similar_product_without_provider->name = $product_with_provider->name;

                        if (!isset($similar_product_without_provider->longdesc) ||
                            strlen($similar_product_without_provider->longdesc) < strlen($product_with_provider->longdesc))
                            $similar_product_without_provider->longdesc = $product_with_provider->longdesc;

                        if (!isset($similar_product_without_provider->ean))
                            $similar_product_without_provider->ean = $product_with_provider->ean;

                        if (!isset($similar_product_without_provider->pn))
                            $similar_product_without_provider->pn = $product_with_provider->pn;

                        if (!isset($similar_product_without_provider->brand_id))
                            $similar_product_without_provider->brand_id = $product_with_provider->brand_id;

                        $similar_product_without_provider->save();

                        // Update Images
                        if ($similar_product_without_provider->images->count() < 6)
                            $similar_product_without_provider->copyImages($product_with_provider);

                        // Update Provider Product Attributes
                        foreach ($product_with_provider->provider_product_attributes as $provider_product_attribute) {
                            ProviderProductAttribute::firstOrCreate([
                                'provider_id'                   => 3,   //vox66api
                                'product_id'                    => $similar_product_without_provider->id,
                                'provider_attribute_id'         => $provider_product_attribute->provider_attribute_id,
                                'provider_attribute_value_id'   => $provider_product_attribute->provider_attribute_value_id,
                            ],[]);
                        }

                        $res[] = $similar_product_without_provider->id;
                    }
            }

            Log::channel('commands')->info('END copy:attributes ' .json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'copy_attributes', $res);
        }
    }
}
