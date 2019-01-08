<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Stock
 * @package Models
 * @mixin Model
 */
class Stock extends Model
{
    protected $table = 'stock';

    protected $fillable = [
        'code',
        'name',
        'price',
        'listed_at',
    ];
}