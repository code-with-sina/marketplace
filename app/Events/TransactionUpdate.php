<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TransactionUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $updateState;
    public $acceptance;
    public $session;

    /**
     * Create a new event instance.
     */
    public function __construct($acceptance, $session, $updateState)
    {
        $this->updateState = $updateState;
        $this->acceptance = $acceptance;
        $this->session = $session;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('transUpdate.'.$this->acceptance.'-'.$this->session),
        ];
    }

    public function broadcastWith()
    {
        return [
            'updateState'       => $this->updateState,
           
        ];
    }
}
