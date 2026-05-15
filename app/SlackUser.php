<?php

namespace App;

use Database\Factories\SlackUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackUser extends Model
{
    use HasFactory;

    protected static string $factory = SlackUserFactory::class;

    public $guarded = [];
}
