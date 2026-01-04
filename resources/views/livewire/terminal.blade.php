<div class="flex flex-col h-screen max-w-4xl mx-auto p-2"
     x-data="{
         interactiveTerminal: null,
         
         // Initialize and restore saved connection from encrypted token
         async init() {
             const token = localStorage.getItem('ssh_session_token');
             if (token) {
                 try {
                     const result = await $wire.restoreFromToken(token);
                     
                     if (result.success && result.token) {
                         localStorage.setItem('ssh_session_token', result.token);
                         // Auto-connect interactive terminal
                         await this.initTerminal();
                     } else {
                         localStorage.removeItem('ssh_session_token');
                         console.log('Session expired or invalid:', result.message);
                     }
                 } catch (e) {
                     console.error('Failed to restore session:', e);
                     localStorage.removeItem('ssh_session_token');
                 }
             }
         },
         
         async saveConnection() {
             try {
                 const token = await $wire.generateSessionToken();
                 if (token) {
                     localStorage.setItem('ssh_session_token', token);
                 }
             } catch (e) {
                 console.error('Failed to save session:', e);
             }
         },
         
         async initTerminal() {
             await $nextTick();
             if (typeof InteractiveTerminal !== 'undefined') {
                 this.interactiveTerminal = new InteractiveTerminal('xterm-container');
                 this.interactiveTerminal.mount();
                 await this.interactiveTerminal.connect($wire.host, $wire.username, $wire.password);
                 this.interactiveTerminal.focus();
             } else {
                 console.error('InteractiveTerminal not loaded');
             }
         },
         
         async connectSSH() {
             const result = await $wire.testConnection();
             if (result && result.success && result.token) {
                 localStorage.setItem('ssh_session_token', result.token);
                 await this.initTerminal();
             }
         },
         
         async disconnect() {
             localStorage.removeItem('ssh_session_token');
             if (this.interactiveTerminal) {
                 await this.interactiveTerminal.disconnect();
                 this.interactiveTerminal = null;
             }
             $wire.host = '';
             $wire.username = '';
             $wire.password = '';
             $wire.isConnected = false;
         }
     }"
>
    <!-- Connection Bar -->
    <div class="bg-gray-800 p-3 rounded mb-4 flex gap-2 flex-wrap" x-data="{ expanded: !$wire.isConnected }">
        <div class="w-full flex justify-between items-center mb-2">
             <span class="text-gray-400 text-sm font-bold">
                 🖥️ Remote Terminal
                 <span x-show="$wire.isConnected" class="text-green-400 ml-2">● Connected</span>
             </span>
             <div class="flex gap-2">
                 <button x-show="$wire.isConnected" 
                         @click="disconnect()" 
                         class="text-xs text-red-400 hover:text-red-300 transition">
                     Disconnect
                 </button>
                 <button @click="expanded = !expanded" class="text-xs text-blue-400 hover:text-blue-300 transition" x-text="expanded ? 'Hide' : 'Show'"></button>
             </div>
        </div>
        
        <div x-show="expanded" x-transition class="w-full flex gap-2 flex-wrap text-sm">
            <input wire:model="host" type="text" placeholder="Host IP" class="bg-gray-700 border border-gray-600 p-2 rounded text-white flex-1 min-w-[140px] focus:border-green-500 focus:outline-none">
            <input wire:model="username" type="text" placeholder="User" class="bg-gray-700 border border-gray-600 p-2 rounded text-white w-24 focus:border-green-500 focus:outline-none">
            <input wire:model="password" type="password" placeholder="Pass" class="bg-gray-700 border border-gray-600 p-2 rounded text-white w-24 focus:border-green-500 focus:outline-none">
            <button @click="connectSSH()" 
                    class="bg-blue-600 px-4 py-2 rounded text-white font-bold hover:bg-blue-500 transition shadow-lg">
                Connect
            </button>
        </div>
    </div>

    <!-- Interactive Terminal Container (xterm.js) - Always visible when connected -->
    <div x-show="$wire.isConnected" 
         x-transition
         class="flex-1 mb-4 rounded overflow-hidden border border-gray-700 shadow-lg bg-[#1a1b26]"
    >
        <div id="xterm-container" class="h-full w-full" style="min-height: 500px;"></div>
    </div>

    <!-- Welcome Message (when not connected) -->
    <div x-show="!$wire.isConnected" 
         class="flex-1 bg-gray-900 rounded p-8 mb-4 flex flex-col items-center justify-center text-center border border-gray-800"
    >
        <div class="text-6xl mb-4">🖥️</div>
        <h2 class="text-2xl font-bold text-white mb-2">Remote Terminal</h2>
        <p class="text-gray-400 mb-4">Connect to your SSH server to start</p>
        <p class="text-gray-600 text-xs mt-4">Developed by Rahman Umardi</p>
    </div>

    <!-- Loading Indicator -->
    <div wire:loading wire:target="testConnection" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-gray-800 p-4 rounded-lg flex items-center gap-3">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-white">Connecting...</span>
        </div>
    </div>
</div>
