<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * SSH Terminal Service
 * 
 * Executes SSH commands per-request using cached credentials.
 * This approach works with PHP's request-response model.
 */
class SshTerminalService
{
    /** Connection timeout in seconds */
    private const CONNECTION_TIMEOUT = 30;

    /**
     * Test SSH connection and cache credentials
     */
    public function connect(string $connectionId, string $host, string $username, string $password, int $port = 22): array
    {
        try {
            $ssh = new SSH2($host, $port, self::CONNECTION_TIMEOUT);
            
            if (!$ssh->login($username, $password)) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed'
                ];
            }

            // Cache the credentials (encrypted) for future requests
            $credentials = Crypt::encryptString(json_encode([
                'host' => $host,
                'port' => $port,
                'username' => $username,
                'password' => $password,
            ]));
            
            Cache::put("ssh_session_{$connectionId}", $credentials, now()->addHours(24));
            
            // Get initial shell output
            $ssh->enablePTY();
            $ssh->setTimeout(2);
            $ssh->exec('');
            $initialOutput = $ssh->read();
            
            // Store initial output for first read
            Cache::put("ssh_output_{$connectionId}", $initialOutput, now()->addMinutes(1));
            
            $ssh->disconnect();

            return [
                'success' => true,
                'message' => "Connected to {$host}"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute command and return output
     */
    public function executeCommand(string $connectionId, string $command): ?string
    {
        $credentials = $this->getCredentials($connectionId);
        if (!$credentials) {
            return null;
        }

        try {
            $ssh = new SSH2($credentials['host'], $credentials['port'], self::CONNECTION_TIMEOUT);
            
            if (!$ssh->login($credentials['username'], $credentials['password'])) {
                return null;
            }

            $ssh->enablePTY();
            $ssh->setTimeout(10);
            
            // Execute command with PTY
            $output = $ssh->exec($command);
            
            $ssh->disconnect();
            
            return $output;
            
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Write input and read output (for interactive mode)
     */
    public function writeAndRead(string $connectionId, string $input): ?string
    {
        $credentials = $this->getCredentials($connectionId);
        if (!$credentials) {
            return null;
        }

        try {
            $ssh = new SSH2($credentials['host'], $credentials['port'], self::CONNECTION_TIMEOUT);
            
            if (!$ssh->login($credentials['username'], $credentials['password'])) {
                return null;
            }

            $ssh->enablePTY();
            $ssh->setTimeout(3);
            
            // Execute the input as a command
            if (trim($input) !== '') {
                $output = $ssh->exec(trim($input));
            } else {
                $output = $ssh->exec('echo ""');
            }
            
            $ssh->disconnect();
            
            return $output;
            
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Read initial/cached output
     */
    public function read(string $connectionId): ?string
    {
        $output = Cache::pull("ssh_output_{$connectionId}");
        return $output ?: null;
    }

    /**
     * Resize terminal (no-op for now, handled client-side)
     */
    public function resize(string $connectionId, int $cols, int $rows): bool
    {
        return $this->hasConnection($connectionId);
    }

    /**
     * Disconnect/clear session
     */
    public function disconnect(string $connectionId): bool
    {
        Cache::forget("ssh_session_{$connectionId}");
        Cache::forget("ssh_output_{$connectionId}");
        return true;
    }

    /**
     * Check if credentials exist in cache
     */
    public function hasConnection(string $connectionId): bool
    {
        return Cache::has("ssh_session_{$connectionId}");
    }

    /**
     * Get decrypted credentials from cache
     */
    private function getCredentials(string $connectionId): ?array
    {
        $encrypted = Cache::get("ssh_session_{$connectionId}");
        if (!$encrypted) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($encrypted);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
