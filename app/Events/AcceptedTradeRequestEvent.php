<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcceptedTradeRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $owner;
    public $recipient;
    public $amount;
    public $item;
    public $item_id;
    public $wallet_name;
    public $acceptance_id;
    public $session_id;
    public $amountInNaira;

    /**
     * Create a new event instance.
     */
    public function __construct($owner, $recipient, $amount, $amountInNaira, $item,  $item_id, $wallet_name,  $acceptance_id, $session_id)
    {

        $this->owner            =   $owner;
        $this->recipient        =   $recipient;
        $this->amount           =   $amount;
        $this->item             =   $item;
        $this->item_id          =   $item_id;
        $this->wallet_name      =   $wallet_name;
        $this->acceptance_id    =   $acceptance_id;
        $this->session_id       =   $session_id;
        $this->amountInNaira    =   $amountInNaira;
    }
}
