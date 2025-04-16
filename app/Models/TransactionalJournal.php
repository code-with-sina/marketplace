<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionalJournal extends Model
{
    use HasFactory;
    protected $fillable = [
        "account_type",
        "narration",
        "source_account",
        "source_name",
        "source_type",
        "destination_account",
        "destination_name",
        "destination_type",
        "amount",
        "source_reference",
        "api_reference",
        "status",
        'trnx_id',
        'reason_for_failure',
    ];
}
