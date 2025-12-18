<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Order $order): bool
    {
        if ($user !== null) {
            return true;
        }

        $deviceId = session('device_id');
        $tableId = session('table_id');

        if (!$deviceId || !$tableId) {
            return false;
        }

        return $order->belongsToContext($tableId, $deviceId);
    }

    public function create(?User $user): bool
    {
        if ($user !== null) {
            return true;
        }

        return session()->has('table_id') && session()->has('device_id');
    }

    public function update(?User $user, Order $order): bool
    {
        return $user !== null;
    }

    public function delete(?User $user, Order $order): bool
    {
        return $user !== null;
    }

    public function cancel(?User $user, Order $order): bool
    {
        if ($user !== null) {
            return true;
        }

        $deviceId = session('device_id');
        $tableId = session('table_id');

        if (!$deviceId || !$tableId) {
            return false;
        }

        return $order->belongsToContext($tableId, $deviceId) && $order->canBeCancelled();
    }

    public function viewAllOrders(?User $user): bool
    {
        return $user !== null;
    }
}
