<?php

namespace account\Events;

use account\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use account\App;

class AppCreateEvent extends Event
{
    use SerializesModels;
    
    public $token;
    public $access_token;
    public $app_id;
    public $users;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($token, $access_token, $app_id, $users)
    {
    	$this->token = $token;
    	$this->access_token = $access_token;
    	$this->app_id = $app_id;
    	$this->users = $users;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
