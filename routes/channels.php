<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Order channels
Broadcast::channel('order.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// User session channels
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Chat channels
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Booking request channels - for users
Broadcast::channel('booking-request.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Booking request channels - for admin (requires admin role)
Broadcast::channel('booking-request.admin', function ($user) {
    return $user->hasRole('admin');
});
