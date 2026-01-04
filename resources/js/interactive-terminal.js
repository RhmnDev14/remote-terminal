/**
 * Interactive Terminal using xterm.js with WebSocket SSH Proxy
 * Provides PTY support for applications like nano, vim, htop
 */

import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import { WebLinksAddon } from '@xterm/addon-web-links';

export class InteractiveTerminal {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.ws = null;
        this.isConnected = false;
        
        // WebSocket server URL (SSH Proxy)
        this.wsUrl = options.wsUrl || 'ws://localhost:2222';
        
        // xterm.js instance
        this.terminal = new Terminal({
            cursorBlink: true,
            cursorStyle: 'block',
            fontFamily: 'JetBrains Mono, Menlo, Monaco, Consolas, monospace',
            fontSize: options.fontSize || 14,
            theme: {
                background: '#1a1b26',
                foreground: '#a9b1d6',
                cursor: '#c0caf5',
                cursorAccent: '#1a1b26',
                selection: '#33467c',
                black: '#32344a',
                red: '#f7768e',
                green: '#9ece6a',
                yellow: '#e0af68',
                blue: '#7aa2f7',
                magenta: '#ad8ee6',
                cyan: '#449dab',
                white: '#787c99',
                brightBlack: '#444b6a',
                brightRed: '#ff7a93',
                brightGreen: '#b9f27c',
                brightYellow: '#ff9e64',
                brightBlue: '#7da6ff',
                brightMagenta: '#bb9af7',
                brightCyan: '#0db9d7',
                brightWhite: '#acb0d0',
            },
            allowProposedApi: true,
        });

        // Addons
        this.fitAddon = new FitAddon();
        this.terminal.loadAddon(this.fitAddon);
        this.terminal.loadAddon(new WebLinksAddon());
        
        // Bind resize handler
        this.handleResize = this.handleResize.bind(this);
    }

    /**
     * Initialize terminal in container
     */
    mount() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error(`Container #${this.containerId} not found`);
            return false;
        }

        this.terminal.open(container);
        this.fitAddon.fit();

        // Handle window resize
        window.addEventListener('resize', this.handleResize);

        // Handle terminal input - send to WebSocket
        this.terminal.onData((data) => {
            this.sendInput(data);
        });

        this.terminal.writeln('\x1b[1;32m✓ Remote terminal ready\x1b[0m');
        this.terminal.writeln('\x1b[90mConnecting to SSH proxy...\x1b[0m');

        return true;
    }

    /**
     * Handle window resize
     */
    handleResize() {
        this.fitAddon.fit();
        this.sendResize();
    }

    /**
     * Connect to SSH server via WebSocket proxy
     */
    async connect(host, username, password) {
        return new Promise((resolve, reject) => {
            try {
                // Connect to WebSocket SSH proxy
                this.ws = new WebSocket(this.wsUrl);
                
                this.ws.onopen = () => {
                    console.log('WebSocket connected to SSH proxy');
                    
                    // Send SSH connection request
                    this.ws.send(JSON.stringify({
                        type: 'connect',
                        host: host,
                        port: 22,
                        username: username,
                        password: password,
                        cols: this.terminal.cols,
                        rows: this.terminal.rows,
                    }));
                };
                
                this.ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        
                        switch (data.type) {
                            case 'connected':
                                this.isConnected = true;
                                this.terminal.writeln(`\x1b[1;32m✓ ${data.message}\x1b[0m`);
                                this.terminal.writeln('');
                                resolve(true);
                                break;
                                
                            case 'output':
                                this.terminal.write(data.data);
                                break;
                                
                            case 'error':
                                this.terminal.writeln(`\x1b[1;31m✗ ${data.message}\x1b[0m`);
                                if (!this.isConnected) {
                                    resolve(false);
                                }
                                break;
                                
                            case 'disconnected':
                                this.isConnected = false;
                                this.terminal.writeln(`\x1b[90m\r\n${data.message}\x1b[0m`);
                                break;
                        }
                    } catch (err) {
                        console.error('Message parse error:', err);
                    }
                };
                
                this.ws.onerror = (err) => {
                    console.error('WebSocket error:', err);
                    this.terminal.writeln('\x1b[1;31m✗ WebSocket connection failed\x1b[0m');
                    this.terminal.writeln('\x1b[90mMake sure SSH proxy server is running: npm run ssh-proxy\x1b[0m');
                    resolve(false);
                };
                
                this.ws.onclose = () => {
                    console.log('WebSocket closed');
                    this.isConnected = false;
                };
                
            } catch (error) {
                this.terminal.writeln(`\x1b[1;31m✗ Connection error: ${error.message}\x1b[0m`);
                resolve(false);
            }
        });
    }

    /**
     * Send input to SSH via WebSocket
     */
    sendInput(input) {
        if (!this.isConnected || !this.ws || this.ws.readyState !== WebSocket.OPEN) {
            return;
        }

        this.ws.send(JSON.stringify({
            type: 'input',
            data: input,
        }));
    }

    /**
     * Send resize event
     */
    sendResize() {
        if (!this.isConnected || !this.ws || this.ws.readyState !== WebSocket.OPEN) {
            return;
        }

        this.ws.send(JSON.stringify({
            type: 'resize',
            cols: this.terminal.cols,
            rows: this.terminal.rows,
        }));
    }

    /**
     * Disconnect from server
     */
    async disconnect() {
        window.removeEventListener('resize', this.handleResize);
        
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'disconnect',
            }));
            this.ws.close();
        }

        this.isConnected = false;
        this.ws = null;
        this.terminal.writeln('\x1b[90m\r\nDisconnected.\x1b[0m');
    }

    /**
     * Clear terminal
     */
    clear() {
        this.terminal.clear();
    }

    /**
     * Focus terminal
     */
    focus() {
        this.terminal.focus();
    }

    /**
     * Dispose terminal
     */
    dispose() {
        this.disconnect();
        this.terminal.dispose();
    }
}

// Export for use in blade templates
window.InteractiveTerminal = InteractiveTerminal;
