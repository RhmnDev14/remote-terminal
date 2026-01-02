<?php

namespace App\Livewire;

use Livewire\Component;
use phpseclib3\Net\SSH2;

class Terminal extends Component
{
    public $host = '';
    public $username = '';
    public $password = '';
    public $command = '';
    public $history = [];
    public $isConnected = false;

    protected $rules = [
        'host' => 'required',
        'username' => 'required',
        'password' => 'required',
    ];

    public function mount()
    {
        // Initialize with welcome message
        $this->history[] = ['type' => 'info', 'content' => '⚡ Remote Terminal Access. Developed by Rahman Umardi. Ready to connect.'];
    }

    public function runCommand()
    {
        if (empty($this->command)) {
            return;
        }

        $this->validate();

        try {
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                $this->history[] = ['type' => 'error', 'content' => "Login Failed to {$this->host}"];
                return;
            }

            // Execute command
            $output = $ssh->exec($this->command);
            
            // Add to history
            $this->history[] = ['type' => 'command', 'content' => '$ ' . $this->command];
            if (!empty($output)) {
                $this->history[] = ['type' => 'output', 'content' => $output];
            }

        } catch (\Exception $e) {
            $this->history[] = ['type' => 'error', 'content' => $e->getMessage()];
        }

        // Clear command input
        $this->command = '';
    }

    public function testConnection()
    {
        $this->validate();

        try {
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                $this->history[] = ['type' => 'error', 'content' => "Login Failed to {$this->host}"];
                $this->isConnected = false;
                return;
            }
            
            $this->isConnected = true;
            $this->history[] = ['type' => 'success', 'content' => "Connected to {$this->host} successfully!"];
            
        } catch (\Exception $e) {
            $this->isConnected = false;
            $this->history[] = ['type' => 'error', 'content' => $e->getMessage()];
        }
    }

    public function render()
    {
        return view('livewire.terminal');
    }
}
