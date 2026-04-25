<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;

class OwnedThing extends Model
{
    protected $table = 'owned_things';

    protected $fillable = ['owner_type', 'owner_id', 'label'];
}
