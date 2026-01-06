/**
 * SSH Proxy WebSocket Server
 * 
 * This server maintains persistent SSH connections with PTY support
 * for interactive terminal applications like nano, vim, htop.
 * 
 * Run with: node ssh-proxy-server.cjs
 */

const WebSocket = require('ws');
const { Client } = require('ssh2');

const PORT = process.env.SSH_PROXY_PORT || 2222;

// Create WebSocket server
const wss = new WebSocket.Server({ port: PORT });

console.log(`🚀 SSH Proxy WebSocket Server running on ws://localhost:${PORT}`);

wss.on('connection', (ws) => {
    console.log('📡 New WebSocket connection');
    
    let sshClient = null;
    let stream = null;
    
    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message.toString());
            
            switch (data.type) {
                case 'connect':
                    handleConnect(ws, data, (client, sshStream) => {
                        sshClient = client;
                        stream = sshStream;
                    });
                    break;
                    
                case 'input':
                    if (stream) {
                        stream.write(data.data);
                    }
                    break;
                    
                case 'resize':
                    if (stream) {
                        stream.setWindow(data.rows, data.cols, 0, 0);
                    }
                    break;
                    
                case 'disconnect':
                    cleanup(sshClient, stream);
                    sshClient = null;
                    stream = null;
                    break;
            }
        } catch (err) {
            console.error('Message parse error:', err);
        }
    });
    
    ws.on('close', () => {
        console.log('📴 WebSocket connection closed');
        cleanup(sshClient, stream);
    });
    
    ws.on('error', (err) => {
        console.error('WebSocket error:', err);
        cleanup(sshClient, stream);
    });
});

/**
 * Handle SSH connection request
 */
function handleConnect(ws, data, callback) {
    const { host, port = 22, username, password } = data;
    
    console.log(`🔑 Connecting to ${username}@${host}:${port}`);
    
    const sshClient = new Client();
    
    sshClient.on('ready', () => {
        console.log(`✅ SSH connected to ${host}`);
        
        // Request a PTY and shell
        sshClient.shell({
            term: 'xterm-256color',
            cols: data.cols || 80,
            rows: data.rows || 24,
        }, (err, stream) => {
            if (err) {
                console.error('Shell error:', err);
                sendError(ws, 'Failed to open shell: ' + err.message);
                sshClient.end();
                return;
            }
            
            // Send success message
            ws.send(JSON.stringify({
                type: 'connected',
                message: `Connected to ${host}`
            }));
            
            // Stream SSH output to WebSocket
            stream.on('data', (data) => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'output',
                        data: data.toString('utf8')
                    }));
                }
            });
            
            stream.stderr.on('data', (data) => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'output',
                        data: data.toString('utf8')
                    }));
                }
            });
            
            stream.on('close', () => {
                console.log('🔌 SSH stream closed');
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'disconnected',
                        message: 'SSH connection closed'
                    }));
                }
                sshClient.end();
            });
            
            callback(sshClient, stream);
        });
    });
    
    sshClient.on('error', (err) => {
        console.error('SSH error:', err);
        sendError(ws, 'SSH connection error: ' + err.message);
    });
    
    sshClient.on('close', () => {
        console.log('🔌 SSH connection closed');
    });
    
    // Connect to SSH server
    sshClient.connect({
        host: host,
        port: port,
        username: username,
        password: password,
        // Accept any host key (for development - should verify in production)
        algorithms: {
            serverHostKey: ['ssh-rsa', 'ssh-dss', 'ecdsa-sha2-nistp256', 'ecdsa-sha2-nistp384', 'ecdsa-sha2-nistp521', 'rsa-sha2-256', 'rsa-sha2-512']
        },
        readyTimeout: 10000,
    });
}

/**
 * Send error message to client
 */
function sendError(ws, message) {
    if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({
            type: 'error',
            message: message
        }));
    }
}

/**
 * Cleanup SSH connection
 */
function cleanup(sshClient, stream) {
    if (stream) {
        try {
            stream.close();
        } catch (e) {
            // Ignore
        }
    }
    if (sshClient) {
        try {
            sshClient.end();
        } catch (e) {
            // Ignore
        }
    }
}
