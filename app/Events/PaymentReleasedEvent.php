<?php

namespace App\Events;


use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReleasedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $owner;
    public $recipient;
    public $amount;
    public $item;
    public $wallet_name;
    public $item_id;
    public $amount_to_receive;
    /**
     * Create a new event instance.
     */
    public function __construct($owner, $recipient, $amount, $item, $wallet_name, $item_id, $amount_to_receive)
    {
        $this->owner = $owner;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->item = $item;
        $this->wallet_name = $wallet_name;
        $this->item_id = $item_id;
        $this->amount_to_receive = $amount_to_receive;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
