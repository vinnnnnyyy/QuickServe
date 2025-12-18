<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TableSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemStatusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $connectedDevices = TableSession::whereIn('status', ['active', 'paid_leaving'])
                ->distinct('device_ip')
                ->count('device_ip');

            $totalActiveSessions = TableSession::whereIn('status', ['active', 'paid_leaving'])->count();

            $maxDevices = 50;

            $serverIp = $request->ip() === '127.0.0.1' || $request->ip() === '::1' 
                ? gethostbyname(gethostname()) 
                : $request->server('SERVER_ADDR');

            $serverPort = $request->server('SERVER_PORT', '8000');

            $databaseConnected = true;
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $databaseConnected = false;
            }

            $baseUrl = 'http://' . $serverIp . ':' . $serverPort . '/table/';

            $dhcpRangeStart = $this->incrementIp($serverIp, 100);
            $dhcpRangeEnd = $this->incrementIp($serverIp, 200);

            return response()->json([
                'wifi_network' => env('WIFI_NETWORK_NAME', 'CafeOrder_WiFi'),
                'connected_devices' => $connectedDevices,
                'active_sessions' => $totalActiveSessions,
                'max_devices' => $maxDevices,
                'server_status' => 'online',
                'database_status' => $databaseConnected ? 'connected' : 'disconnected',
                'network_info' => [
                    'server_ip' => $serverIp,
                    'server_port' => $serverPort,
                    'dhcp_range' => $this->extractIpEnd($dhcpRangeStart) . '-' . $this->extractIpEnd($dhcpRangeEnd),
                    'base_url' => $baseUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'wifi_network' => env('WIFI_NETWORK_NAME', 'CafeOrder_WiFi'),
                'connected_devices' => 0,
                'active_sessions' => 0,
                'max_devices' => 50,
                'server_status' => 'online',
                'database_status' => 'error',
                'network_info' => [
                    'server_ip' => 'N/A',
                    'server_port' => 'N/A',
                    'dhcp_range' => 'N/A',
                    'base_url' => 'N/A',
                ],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function incrementIp(string $ip, int $increment): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = (int)$parts[3] + $increment;
            return implode('.', $parts);
        }
        return $ip;
    }

    private function extractIpEnd(string $ip): string
    {
        $parts = explode('.', $ip);
        return end($parts);
    }
}
