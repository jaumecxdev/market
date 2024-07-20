<?php

namespace App;

use App\Shop;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Permission\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User role($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function getSuppliersId()
    {
        if ($this->hasRole('admin')) {
            return Supplier::pluck('id');
        }
        else {
            $permission = $this->getPermissionNames()->filter(function ($value, $key) {
                return substr($value, 0, 9) == 'suppliers';
            })->first();

            // suppliers.*.1,8,10,11,12,13,14
            if ($permission) return explode(',', substr($permission, 12));

            return [];
        }
    }


    public function getShopsId()
    {
        $permission = $this->getPermissionNames()->filter(function ($value, $key) {
            return substr($value, 0, 5) == 'shops';
        })->first();

        // shops.*.9,11,12
        if ($permission) return explode(',', substr($permission, 8));

        return [];
    }


    public function getMarketsId()
    {
        if ($shops_id = $this->getShopsId())
            return Shop::find($shops_id)->pluck('market_id')->toArray();      // return: [9,12,13];

            return [];
    }


    public function getSuppliers()
    {
        if ($this->hasRole('admin')) {
            return Supplier::orderBy('name', 'asc')->get();
        }
        else {
            if ($suppliers_id = $this->getSuppliersId())
                return Supplier::orderBy('name', 'asc')->find($suppliers_id);

            return [];
        }
    }


    public function checkSupplier(Supplier $supplier)
    {
        if ($this->hasRole('seller'))
            if (!in_array($supplier->id, $this->getSuppliersId())) return null;

        return $supplier;
    }



    public function checkShop(Shop $shop)
    {
        if ($this->hasRole('seller'))
            if (!in_array($shop->id, $this->getShopsId())) return null;

        return $shop;
    }


    public function checkMarket(Market $market)
    {
        if ($this->hasRole('seller'))
            if (!in_array($market->id, $this->getMarketsId())) return null;

        return $market;
    }

}
