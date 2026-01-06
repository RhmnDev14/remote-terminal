<?php

namespace App\Http\Controllers;

use App\Services\SshTerminalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Terminal API Controller
 * 
 * Handles SSH terminal connections and I/O via HTTP/WebSocket
 */
class TerminalController extends Controller
{
    public function __construct(
        private SshTerminalService $sshService
    ) {}

    /**
     * Connect to SSH server
     * POST /api/terminal/connect
     */
    public function connect(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'port' => 'integer|min:1|max:65535',
        ]);

        // Generate unique connection ID
        $connectionId = Str::uuid()->toString();

        $result = $this->sshService->connect(
            $connectionId,
            $validated['host'],
            $validated['username'],
            $validated['password'],
            $validated['port'] ?? 22
        );

        if (!$result['success']) {
            return response()->json($result, 401);
        }

        // Create encrypted token with connection info
        $token = Crypt::encryptString(json_encode([
            'connectionId' => $connectionId,
            'host' => $validated['host'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'port' => $validated['port'] ?? 22,
            'exp' => time() + (24 * 60 * 60), // 24 hours
        ]));

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'connectionId' => $connectionId,
            'token' => $token,
        ]);
    }

    /**
     * Send input to terminal and get output
     * POST /api/terminal/input
     */
    public function input(Request $request)
    {
        $validated = $request->validate([
            'connectionId' => 'required|string',
            'data' => 'required|string',
        ]);

        if (!$this->sshService->hasConnection($validated['connectionId'])) {
            return response()->json([
                'success' => false,
                'message' => 'Connection not found'
            ], 404);
        }

        $output = $this->sshService->writeAndRead($validated['connectionId'], $validated['data']);

        return response()->json([
            'success' => true,
            'data' => $output,
        ]);
    }

    /**
     * Read output from terminal
     * POST /api/terminal/read
     */
    public function read(Request $request)
    {
        $validated = $request->validate([
            'connectionId' => 'required|string',
        ]);

        if (!$this->sshService->hasConnection($validated['connectionId'])) {
            return response()->json([
                'success' => false,
                'message' => 'Connection not found'
            ], 404);
        }

        $output = $this->sshService->read($validated['connectionId']);

        return response()->json([
            'success' => true,
            'data' => $output,
        ]);
    }

    /**
     * Resize terminal
     * POST /api/terminal/resize
     */
    public function resize(Request $request)
    {
        $validated = $request->validate([
            'connectionId' => 'required|string',
            'cols' => 'required|integer|min:1',
            'rows' => 'required|integer|min:1',
        ]);

        if (!$this->sshService->hasConnection($validated['connectionId'])) {
            return response()->json([
                'success' => false,
                'message' => 'Connection not found'
            ], 404);
        }

        $this->sshService->resize(
            $validated['connectionId'],
            $validated['cols'],
            $validated['rows']
        );

        return response()->json(['success' => true]);
    }

    /**
     * Disconnect terminal
     * POST /api/terminal/disconnect
     */
    public function disconnect(Request $request)
    {
        $validated = $request->validate([
            'connectionId' => 'required|string',
        ]);

        $this->sshService->disconnect($validated['connectionId']);

        return response()->json(['success' => true]);
    }

    /**
     * Restore session from encrypted token
     * POST /api/terminal/restore
     */
    public function restore(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $decrypted = Crypt::decryptString($validated['token']);
            $payload = json_decode($decrypted, true);

            if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }

            // Reconnect using stored credentials
            $connectionId = $payload['connectionId'];
            
            // Check if already connected
            if ($this->sshService->hasConnection($connectionId)) {
                return response()->json([
                    'success' => true,
                    'connectionId' => $connectionId,
                    'message' => 'Session restored'
                ]);
            }

            // Reconnect
            $result = $this->sshService->connect(
                $connectionId,
                $payload['host'],
                $payload['username'],
                $payload['password'],
                $payload['port'] ?? 22
            );

            if (!$result['success']) {
                return response()->json($result, 401);
            }

            // Generate new token with refreshed expiry
            $newToken = Crypt::encryptString(json_encode([
                'connectionId' => $connectionId,
                'host' => $payload['host'],
                'username' => $payload['username'],
                'password' => $payload['password'],
                'port' => $payload['port'] ?? 22,
                'exp' => time() + (24 * 60 * 60),
            ]));

            return response()->json([
                'success' => true,
                'connectionId' => $connectionId,
                'token' => $newToken,
                'message' => 'Session restored'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }
    }
}
