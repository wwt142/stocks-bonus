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
    protected $table = 'stocks';

    protected $fillable = [
        'code',
        'name',
        'price',
        'listed_at',
        'pb',
        'pe',
        'mc',
        'roe',
        'dy',
    ];
}