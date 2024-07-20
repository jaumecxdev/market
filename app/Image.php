<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


/**
 * App\Image
 *
 * @property int $id
 * @property int|null $product_id
 * @property int $type
 * @property string $src
 * @property-read \App\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image whereSrc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Image whereType($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    protected $table = 'images';

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'type', 'src'
    ];


    public function product()
    {
        return $this->belongsTo('App\Product');
    }


    // CUSTOM


    public function getUrl()
    {
        return Storage::url('img/' .$this->product_id. '/');
    }

    public function getFullUrl()
    {
        return Storage::url('img/' .$this->product_id. '/' .$this->src);
    }

    public static function getNoImageFullUrl($filename)
    {
        return url(Storage::url('img/' .$filename));
    }

    public function getPath()
    {
        return storage_path('img/' .$this->product_id. '/');
    }

    public function getFullPath()
    {
        return storage_path('img/' .$this->product_id. '/' .$this->src);
    }

}
