<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycState extends Model
{
    use HasFactory;
    protected $fillable = [
        'bvn',
        'selfieImage',
        'idType',
        'idNumber',
        'dateOfBirth',
        'gender',
        'status',
    ];
}
