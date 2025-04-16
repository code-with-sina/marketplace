<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardCustomerServiceTrack extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'method', 'error_message', 'statefulError', 'editState'];
}
