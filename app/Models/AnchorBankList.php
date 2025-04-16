<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnchorBankList extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'nipcode',
        'name',
        'cbncode'
    ];
}
