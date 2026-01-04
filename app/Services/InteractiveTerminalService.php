<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use Illuminate\Support\Str;

class InteractiveTerminalService
{
    private ?SSH2 $ssh = null;
    private string $sessionId;
    private int $cols = 80;
    private int $rows = 24;

    /**
     * Connect to SSH server with PTY
     */
    public function connect(string $host, string $username, string $password, int $cols = 80, int $rows = 24): array
    {
        $this->cols = $cols;
        $this->rows = $rows;

        try {
            $this->ssh = new SSH2($host, 22, 10); // 10 second timeout
            
            if (!$this->ssh->login($username, $password)) {
                return [
                    'success' => false,
                    'message' => 'Authentication failed'
                ];
            }

            // Enable PTY for interactive applications
            $this->ssh->enablePTY();
            $this->ssh->setTerminal('xterm-256color');
            $this->ssh->setWindowSize($cols, $rows);

            // Start shell
            $this->ssh->exec(''); // Initialize the shell

            $this->sessionId = Str::uuid()->toString();

            return [
                'success' => true,
                'sessionId' => $this->sessionId
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Write input to the terminal
     */
    public function write(string $data): bool
    {
        if (!$this->ssh) {
            return false;
        }

        try {
            $this->ssh->write($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Read output from the terminal
     */
    public function read(): string
    {
        if (!$this->ssh) {
            return '';
        }

        try {
            // Non-blocking read
            return $this->ssh->read() ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Resize the terminal window
     */
    public function resize(int $cols, int $rows): bool
    {
        if (!$this->ssh) {
            return false;
        }

        try {
            $this->cols = $cols;
            $this->rows = $rows;
            $this->ssh->setWindowSize($cols, $rows);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Close the SSH connection
     */
    public function disconnect(): void
    {
        if ($this->ssh) {
            $this->ssh->disconnect();
            $this->ssh = null;
        }
    }

    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->ssh !== null && $this->ssh->isConnected();
    }

    /**
     * Get current dimensions
     */
    public function getDimensions(): array
    {
        return [
            'cols' => $this->cols,
            'rows' => $this->rows
        ];
    }
}
