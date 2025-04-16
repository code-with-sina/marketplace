<?php

namespace App\Events;



use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class Update implements ShouldBroadcastNow
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
            new Channel('Update.' . $this->acceptance . '-' . $this->session),
        ];
    }

    public function broadcastWith()
    {
        return [
            'updateState'       => $this->updateState,
        ];
    }
}
