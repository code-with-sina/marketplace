<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Support\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionChat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $admin;
    public $sender;
    public $session;
    public $message;
    public $receiver;
    public $filename;
    public $acceptance;
    public $contentType;
    /**
     * Create a new event instance.
     */
    public function __construct($acceptance, $session,  $sender, $receiver, $admin = null, $message, $filename = null, $contentType)
    {
        $this->session = $session;
        $this->acceptance = $acceptance;
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->message = $message;
        $this->filename = $filename;
        $this->contentType = $contentType;
        $this->admin = $admin;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('Chat.'.$this->acceptance.'-'.$this->session),
        ];
    }


    public function broadcastWith()
    {
        
        Chat::create([
            'session'       => $this->session,
            'acceptance'    => $this->acceptance,
            'sender'        => $this->sender,
            'receiver'      => $this->receiver,
            'content'       => $this->message ?? null,
            'image'         => $this->filename ?? null,
            'timestamp'     => Carbon::now(),
            'admin'         => $this->admin ?? null,
            'status'        => 'seen',
            'contentType'   => $this->contentType,
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now()
        ]);
        return [
            'session'       => $this->session,
            'acceptance'    => $this->acceptance,
            'sender'        => $this->sender,
            'receiver'      => $this->receiver,
            'content'       => $this->message ?? null,
            'image'         => $this->filename ?? null,
            'timestamp'     => Carbon::now(),
            'admin'         => $this->admin ?? null,
            'status'        => 'sent',
            'contentType'   => $this->contentType,
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now()
        ];
    }
}
