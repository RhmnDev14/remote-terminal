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
    public $command = '';
    public $history = [];
    public $isConnected = false;
    public $currentDirectory = '~'; // Track current working directory

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

            $commandToDisplay = $this->command;
            $commandToExecute = $this->command;
            
            // Get the base command (first word)
            $baseCommand = explode(' ', trim($this->command))[0];
            
            // List of interactive commands that require PTY
            $interactiveCommands = [
                'nano', 'vim', 'vi', 'nvim', 'emacs', 'pico', 'joe', 'mcedit',  // editors
                'htop', 'top', 'btop', 'nmon', 'glances',  // monitors
                'less', 'more', 'most',  // pagers
                'ssh', 'telnet', 'ftp', 'sftp',  // network
                'mysql', 'psql', 'mongo', 'redis-cli',  // databases
                'python', 'python3', 'node', 'php', 'irb', 'ruby',  // REPLs (without args)
                'bash', 'sh', 'zsh', 'fish',  // shells
                'tmux', 'screen',  // multiplexers
                'man', 'info',  // documentation
            ];
            
            // Check if it's an interactive command
            if (in_array($baseCommand, $interactiveCommands)) {
                $this->history[] = ['type' => 'command', 'content' => '$ ' . $commandToDisplay];
                $this->history[] = ['type' => 'error', 'content' => "⚠️ '{$baseCommand}' requires Interactive Mode."];
                $this->history[] = ['type' => 'info', 'content' => "💡 Enable '🖥️ Interactive Mode' checkbox above to use nano, vim, htop etc."];
                
                $this->command = '';
                return;
            }

            // Check if this is a cd command
            if (preg_match('/^cd\s+(.+)$/', trim($this->command), $matches)) {
                $targetDir = trim($matches[1]);
                
                // Build the full command: cd to current directory, then cd to target, then pwd
                $fullCommand = "cd {$this->currentDirectory} && cd {$targetDir} && pwd";
                $newPath = trim($ssh->exec($fullCommand));
                
                if (!empty($newPath) && strpos($newPath, '/') === 0) {
                    // cd was successful, update current directory
                    $this->currentDirectory = $newPath;
                    $this->history[] = ['type' => 'command', 'content' => '$ ' . $commandToDisplay];
                    // No output for successful cd (like real terminal)
                } else {
                    // cd failed
                    $this->history[] = ['type' => 'command', 'content' => '$ ' . $commandToDisplay];
                    $errorOutput = $ssh->exec("cd {$this->currentDirectory} && cd {$targetDir} 2>&1");
                    $this->history[] = ['type' => 'error', 'content' => $errorOutput ?: "cd: {$targetDir}: No such file or directory"];
                }
            } elseif (trim($this->command) === 'cd') {
                // cd with no args goes to home directory
                $this->currentDirectory = '~';
                $this->history[] = ['type' => 'command', 'content' => '$ cd'];
            } elseif (trim($this->command) === 'pwd') {
                // Handle pwd command to show current tracked directory
                $fullCommand = "cd {$this->currentDirectory} && pwd";
                $output = trim($ssh->exec($fullCommand));
                $this->history[] = ['type' => 'command', 'content' => '$ pwd'];
                if (!empty($output)) {
                    $this->history[] = ['type' => 'output', 'content' => $output];
                }
            } else {
                // For all other commands, prepend cd to current directory
                $fullCommand = "cd {$this->currentDirectory} && {$commandToExecute}";
                $output = $ssh->exec($fullCommand);
                
                // Add to history
                $this->history[] = ['type' => 'command', 'content' => '$ ' . $commandToDisplay];
                if (!empty($output)) {
                    $this->history[] = ['type' => 'output', 'content' => $output];
                }
            }

        } catch (\Exception $e) {
            $this->history[] = ['type' => 'error', 'content' => $e->getMessage()];
        }

        // Clear command input
        $this->command = '';
    }
    
    /**
     * Get suggested alternatives for interactive commands
     */
    private function getSuggestedAlternative($command, $fullCommand)
    {
        // Extract filename if present
        $parts = explode(' ', trim($fullCommand));
        $filename = isset($parts[1]) ? $parts[1] : 'filename';
        
        $suggestions = [
            'nano' => "cat {$filename} (view) | echo 'text' >> {$filename} (append) | sed -i 's/old/new/g' {$filename} (replace)",
            'vim' => "cat {$filename} (view) | echo 'text' >> {$filename} (append) | sed -i 's/old/new/g' {$filename} (replace)",
            'vi' => "cat {$filename} (view) | echo 'text' >> {$filename} (append)",
            'less' => "cat {$filename} | head -n 50 {$filename} | tail -n 50 {$filename}",
            'more' => "cat {$filename} | head -n 50 {$filename}",
            'htop' => "ps aux | free -h | df -h | uptime",
            'top' => "ps aux --sort=-%cpu | head -20",
            'mysql' => "mysql -e 'SHOW DATABASES;' or mysql -e 'SELECT * FROM table LIMIT 10;'",
            'psql' => "psql -c 'SELECT * FROM table LIMIT 10;'",
            'man' => "{$parts[1]} --help 2>&1 | head -50",
        ];
        
        return $suggestions[$command] ?? null;
    }

    public function testConnection()
    {
        $this->validate();

        try {
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                $this->history[] = ['type' => 'error', 'content' => "Login Failed to {$this->host}"];
                $this->isConnected = false;
                return ['success' => false, 'token' => null];
            }
            
            $this->isConnected = true;
            $this->history[] = ['type' => 'success', 'content' => "Connected to {$this->host} successfully!"];
            
            // Generate encrypted session token
            $token = $this->generateSessionToken();
            return ['success' => true, 'token' => $token];
            
        } catch (\Exception $e) {
            $this->isConnected = false;
            $this->history[] = ['type' => 'error', 'content' => $e->getMessage()];
            return ['success' => false, 'token' => null];
        }
    }

    /**
     * Generate encrypted JWT-like session token
     */
    public function generateSessionToken()
    {
        $payload = [
            'host' => $this->host,
            'username' => $this->username,
            'password' => $this->password,
            'currentDirectory' => $this->currentDirectory,
            'exp' => time() + (24 * 60 * 60), // 24 hours expiry
            'iat' => time(),
        ];

        // Encrypt the entire payload using Laravel's encryption
        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Restore session from encrypted token
     */
    public function restoreFromToken($token)
    {
        try {
            // Decrypt the token
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);

            if (!$payload) {
                return ['success' => false, 'message' => 'Invalid token format'];
            }

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['success' => false, 'message' => 'Token expired'];
            }

            // Restore credentials
            $this->host = $payload['host'] ?? '';
            $this->username = $payload['username'] ?? '';
            $this->password = $payload['password'] ?? '';
            $this->currentDirectory = $payload['currentDirectory'] ?? '~';

            // Test the connection
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                $this->isConnected = false;
                return ['success' => false, 'message' => 'Connection failed'];
            }

            $this->isConnected = true;
            $this->history[] = ['type' => 'success', 'content' => "Session restored. Connected to {$this->host}"];
            
            // Generate new token with refreshed expiry
            $newToken = $this->generateSessionToken();
            return ['success' => true, 'token' => $newToken];

        } catch (\Exception $e) {
            $this->isConnected = false;
            return ['success' => false, 'message' => 'Failed to restore session: ' . $e->getMessage()];
        }
    }
    /**
     * Handle tab completion - find matching commands, files, or folders
     */
    public function tabComplete($partialPath, $isFirstWord = false)
    {
        if (!$this->isConnected) {
            return ['suggestions' => [], 'completed' => ''];
        }

        try {
            $ssh = new SSH2($this->host);
            if (!$ssh->login($this->username, $this->password)) {
                return ['suggestions' => [], 'completed' => ''];
            }

            // If this is the first word, search for commands
            if ($isFirstWord && strpos($partialPath, '/') === false) {
                return $this->completeCommand($ssh, $partialPath);
            }

            // Otherwise, search for files/folders
            return $this->completeFilePath($ssh, $partialPath);

        } catch (\Exception $e) {
            return ['suggestions' => [], 'completed' => ''];
        }
    }

    /**
     * Complete command names using compgen
     */
    private function completeCommand($ssh, $prefix)
    {
        if (empty($prefix)) {
            return ['suggestions' => [], 'completed' => ''];
        }

        // Use compgen to find matching commands
        $escapedPrefix = escapeshellarg($prefix);
        $command = "compgen -c {$escapedPrefix} 2>/dev/null | sort -u | head -20";
        $output = trim($ssh->exec($command));

        if (empty($output)) {
            // Fallback: also try to complete files in current directory
            return $this->completeFilePath($ssh, $prefix);
        }

        $matches = array_filter(array_unique(explode("\n", $output)));

        if (count($matches) === 0) {
            return $this->completeFilePath($ssh, $prefix);
        }

        $suggestions = [];
        foreach ($matches as $match) {
            $suggestions[] = [
                'name' => $match,
                'isDir' => false,
                'isCommand' => true
            ];
        }

        if (count($matches) === 1) {
            // Single match - auto complete with space
            return ['suggestions' => [], 'completed' => $matches[0] . ' '];
        }

        // Multiple matches - find common prefix
        $commonPrefix = $this->findCommonPrefix($matches);
        if (strlen($commonPrefix) > strlen($prefix)) {
            return ['suggestions' => $suggestions, 'completed' => $commonPrefix];
        }

        return ['suggestions' => $suggestions, 'completed' => ''];
    }

    /**
     * Complete file/folder paths
     */
    private function completeFilePath($ssh, $partialPath)
    {
        // Determine the directory to search and the prefix to match
        $searchDir = $this->currentDirectory;
        $prefix = $partialPath;
        
        // If path contains /, split into directory and prefix
        if (strpos($partialPath, '/') !== false) {
            $lastSlash = strrpos($partialPath, '/');
            $searchDir = substr($partialPath, 0, $lastSlash + 1);
            $prefix = substr($partialPath, $lastSlash + 1);
            
            // Make search directory relative to current directory if not absolute
            if (strpos($searchDir, '/') !== 0) {
                $searchDir = $this->currentDirectory . '/' . $searchDir;
            }
        }

        $escapedDir = escapeshellarg($searchDir);
        
        // List files matching the prefix
        $escapedPrefix = addslashes($prefix);
        $command = "cd {$this->currentDirectory} 2>/dev/null; cd {$escapedDir} 2>/dev/null && ls -1 2>/dev/null | grep -i \"^{$escapedPrefix}\" 2>/dev/null | head -20";
        $output = trim($ssh->exec($command));
        
        if (empty($output)) {
            return ['suggestions' => [], 'completed' => ''];
        }

        $matches = array_filter(explode("\n", $output));
        
        if (count($matches) === 0) {
            return ['suggestions' => [], 'completed' => ''];
        }
        
        // Check if each match is a directory
        $suggestions = [];
        foreach ($matches as $match) {
            $isDir = trim($ssh->exec("cd {$this->currentDirectory} 2>/dev/null; cd {$escapedDir} 2>/dev/null && [ -d " . escapeshellarg($match) . " ] && echo 'dir'")) === 'dir';
            $suggestions[] = [
                'name' => $match,
                'isDir' => $isDir,
                'isCommand' => false
            ];
        }
        
        if (count($matches) === 1) {
            // Single match - auto complete
            $completed = $matches[0];
            $isDir = $suggestions[0]['isDir'];
            
            // Reconstruct full path if original had directory component
            if (strpos($partialPath, '/') !== false) {
                $lastSlash = strrpos($partialPath, '/');
                $completed = substr($partialPath, 0, $lastSlash + 1) . $completed;
            }
            
            // Add trailing slash for directories, space for files
            if ($isDir) {
                $completed .= '/';
            }
            
            return ['suggestions' => [], 'completed' => $completed];
        }
        
        // Multiple matches - find common prefix
        $commonPrefix = $this->findCommonPrefix($matches);
        if (strlen($commonPrefix) > strlen($prefix)) {
            // Reconstruct with directory component
            if (strpos($partialPath, '/') !== false) {
                $lastSlash = strrpos($partialPath, '/');
                $commonPrefix = substr($partialPath, 0, $lastSlash + 1) . $commonPrefix;
            }
            return ['suggestions' => $suggestions, 'completed' => $commonPrefix];
        }
        
        return ['suggestions' => $suggestions, 'completed' => ''];
    }

    /**
     * Find common prefix among array of strings
     */
    private function findCommonPrefix($strings)
    {
        if (empty($strings)) return '';
        
        $prefix = $strings[0];
        foreach ($strings as $str) {
            while (strpos($str, $prefix) !== 0) {
                $prefix = substr($prefix, 0, -1);
                if (empty($prefix)) return '';
            }
        }
        return $prefix;
    }

    public function render()
    {
        return view('livewire.terminal');
    }
}
