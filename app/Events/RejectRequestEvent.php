<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RejectRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $recipient;
    public $owner;
    public $wallet_name;
    public $item_id;
    public $amount;
    public $item;
    public $amountInNaira;
    /**
     * Create a new event instance.
     */
    public function __construct($recipient, $owner,  $amount, $amountInNaira, $item, $wallet_name, $item_id)
    {
        $this->recipient    = $recipient;
        $this->owner        = $owner;
        $this->amount       = $amount;
        $this->item         = $item;
        $this->wallet_name  = $wallet_name;
        $this->item_id      = $item_id;
        $this->amountInNaira = $amountInNaira;
    }
}
