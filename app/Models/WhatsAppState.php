<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppState extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'optional_whatsapp_number',
        'status',
        'verified_at',
        'receiptId'
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
