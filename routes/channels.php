<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Note: Following channels are PUBLIC because authorization is checked on backend when events are broadcast
// This simplifies WebSocket setup and avoids Sanctum/cookie auth issues with Reverb
