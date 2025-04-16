<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\User;
use App\Models\Chat as ChatDB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;


class Chat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;
    public $acceptance;
    public $sender;
    public $receiver;
    public $admin;
    public $message;
    public $filename;
    public $contentType;

    /**
     * Create a new event instance.
     */
    public function __construct($acceptance, $session,  $sender, $receiver, $admin = null, $message, $filename = null, $contentType)
    {
        $this->session      = $session;
        $this->acceptance   = $acceptance;
        $this->sender       = $sender;
        $this->receiver     = $receiver;
        $this->admin        = $admin;
        $this->message      = $message;
        $this->filename     = $filename;
        $this->contentType  = $contentType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('Chat.' . $this->acceptance . '-' . $this->session),
        ];
    }

    public function broadcastWith()
    {

        $this->saveChat();
        return [
            'session'       => $this->session,
            'acceptance'    => $this->acceptance,
            'sender'        => $this->getUserDetail($this->sender),
            'receiver'      => $this->getUserDetail($this->receiver),
            'admin'         => $this->admin($this->admin) ?? null,
            // 'admin'         => $this->admin ?? null,
            'content'       => $this->message ?? null,
            'image'         => $this->filename ?? null,
            'timestamp'     => Carbon::now(),
            'status'        => 'sent',
            'contentType'   => $this->contentType,
        ];
    }

    public function getUserDetail($uuid)
    {
        if ($uuid !== null) {
            return User::where('uuid', $uuid)->first() ?? null;
        } else {
            return null;
        }
    }

    public function admin($uuid)
    {
        return [
            'admin' => 'admin',
            'uuid' => $uuid
        ];
    }

    public function saveChat()
    {
        ChatDB::create([
            'session'       => $this->session,
            'acceptance'    => $this->acceptance,
            'sender'        => $this->sender ?? "null",
            'receiver'      => $this->receiver ?? "null",
            'admin'         => $this->admin ?? "null",
            'content'       => $this->message ?? "null",
            'image'         => $this->filename ?? "null",
            'timestamp'     => Carbon::now(),
            'status'        => 'sent',
            'contentType'   => $this->contentType
        ]);
    }
}
