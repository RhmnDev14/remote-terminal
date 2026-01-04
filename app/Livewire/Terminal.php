<?php

namespace App\Livewire;

use Livewire\Component;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Crypt;

class Terminal extends Component
{
    public $host = '';
    public $username = '';
    public $password = '';
    public $isConnected = false;

    protected $rules = [
        'host' => 'required',
        'username' => 'required',
        'password' => 'required',
    ];

    public function testConnection()
    {
        $this->validate();

        try {
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                $this->isConnected = false;
                return ['success' => false, 'token' => null];
            }
            
            $this->isConnected = true;
            
            // Generate encrypted session token
            $token = $this->generateSessionToken();
            return ['success' => true, 'token' => $token];
            
        } catch (\Exception $e) {
            $this->isConnected = false;
            return ['success' => false, 'token' => null];
        }
    }

    /**
     * Generate encrypted session token for auto-reconnect
     */
    public function generateSessionToken()
    {
        $payload = [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'exp' => time() + (24 * 60 * 60), // 24 hours expiry
            'iat' => time(),
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Restore session from encrypted token
     */
    public function restoreFromToken($token)
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);

            if (!$payload) {
                return ['success' => false, 'message' => 'Invalid token format'];
            }

            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['success' => false, 'message' => 'Token expired'];
            }

            $this->host = $payload['host'] ?? '';
            $this->username = $payload['username'] ?? '';
            $this->password = $payload['password'] ?? '';

            // Test the connection
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                $this->isConnected = false;
                return ['success' => false, 'message' => 'Connection failed'];
            }

            $this->isConnected = true;
            
            // Generate new token with refreshed expiry
            $newToken = $this->generateSessionToken();
            return ['success' => true, 'token' => $newToken];

        } catch (\Exception $e) {
            $this->isConnected = false;
            return ['success' => false, 'message' => 'Failed to restore session'];
        }
    }

    public function render()
    {
        return view('livewire.terminal');
    }
}
