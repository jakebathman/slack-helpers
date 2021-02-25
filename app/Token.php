<?php

namespace App;

use App\Slack\SlackClient;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    public $guarded = [];

    public function getGeneralChannelId()
    {
        if (! empty($this->general_channel_id)) {
            return $this->general_channel_id;
        }

        // Get the channel list from the Slack API
        $client = new SlackClient($this->access_token);
        $data = $client->conversationsList();

        // Look for one called "general"
        foreach ($data['channels'] as $channel) {
            if (strtolower($channel['name']) === 'general') {
                $this->general_channel_id = $channel['id'];
                $this->save();

                break;
            }
        }

        return $this->general_channel_id;
    }
}
