<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Stock
 * @package Models
 * @mixin Model
 */
class Bonus extends Model
{
    protected $table = 'bonus';

    protected $fillable = [
        'stock_code',
        'program_desc',
        'report_date',
        'meeting_date',
        'announcement_date',
        'material_date',
        'stock_registration_date',
        'ex_dividend_date',
        'programme_progress',
        'payout_ratio',
        'dividend_rate',
        'dividend_money',
    ];
}