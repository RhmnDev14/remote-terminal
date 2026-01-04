<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use phpseclib3\Net\SSH2;

class TerminalWebSocketController extends Controller
{
    // Store active SSH sessions in cache
    private static $sessions = [];

    /**
     * Start a new interactive terminal session
     */
    public function startSession(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'cols' => 'integer',
            'rows' => 'integer',
        ]);

        try {
            $ssh = new SSH2($request->host);
            
            if (!$ssh->login($request->username, $request->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'SSH authentication failed'
                ], 401);
            }

            // Enable PTY for interactive mode
            $cols = $request->cols ?? 80;
            $rows = $request->rows ?? 24;
            $ssh->enablePTY();
            $ssh->setTerminal('xterm-256color');
            $ssh->setWindowSize($cols, $rows);

            // Start shell
            $ssh->exec('');

            // Generate session ID
            $sessionId = Str::uuid()->toString();
            
            // Store session in cache (using Laravel cache for simplicity)
            cache()->put("terminal_session_{$sessionId}", [
                'ssh' => serialize($ssh),
                'host' => $request->host,
                'username' => $request->username,
                'password' => $request->password,
                'cols' => $cols,
                'rows' => $rows,
                'created_at' => now(),
            ], now()->addHours(2));

            return response()->json([
                'success' => true,
                'sessionId' => $sessionId,
                'message' => 'Terminal session started'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send input to the terminal session
     */
    public function sendInput(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
            'input' => 'required|string',
        ]);

        $sessionData = cache()->get("terminal_session_{$request->sessionId}");
        
        if (!$sessionData) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or expired'
            ], 404);
        }

        try {
            // Reconnect SSH (since we can't store SSH object directly)
            $ssh = new SSH2($sessionData['host']);
            if (!$ssh->login($sessionData['username'], $sessionData['password'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'SSH reconnection failed'
                ], 401);
            }

            $ssh->enablePTY();
            $ssh->setTerminal('xterm-256color');
            $ssh->setWindowSize($sessionData['cols'], $sessionData['rows']);
            
            // Write input to PTY
            $ssh->write($request->input);
            
            // Read output
            usleep(50000); // 50ms delay for output
            $output = $ssh->read();

            return response()->json([
                'success' => true,
                'output' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resize terminal
     */
    public function resize(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
            'cols' => 'required|integer',
            'rows' => 'required|integer',
        ]);

        $sessionData = cache()->get("terminal_session_{$request->sessionId}");
        
        if (!$sessionData) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // Update session dimensions
        $sessionData['cols'] = $request->cols;
        $sessionData['rows'] = $request->rows;
        cache()->put("terminal_session_{$request->sessionId}", $sessionData, now()->addHours(2));

        return response()->json(['success' => true]);
    }

    /**
     * Close terminal session
     */
    public function closeSession(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
        ]);

        cache()->forget("terminal_session_{$request->sessionId}");

        return response()->json([
            'success' => true,
            'message' => 'Session closed'
        ]);
    }
}
