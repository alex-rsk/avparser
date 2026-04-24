<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

 class ParsingCompleteEvent extends BaseParsingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $parsingTimeFrom;
    public $parsingTimeTo;

    public $filePath;
    /**
     * Create a new event instance.
     */
    public function __construct(private ?Order $order = null)
    {        
        parent::__construct($order);
        $this->parsingTimeFrom = date('Y-m-d H:i:s', strtotime('midnight'));
        $this->parsingTimeTo   =  date('Y-m-d H:i:s', strtotime('midnight') + 86400);
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
