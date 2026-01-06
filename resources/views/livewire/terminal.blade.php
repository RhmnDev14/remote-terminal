<div class="flex flex-col h-screen max-w-4xl mx-auto p-2"
     x-data="{
         interactiveTerminal: null,
         connecting: false,
         terminalVisible: false,
         
         // Auto-restore session on page load
         async init() {
             const savedSession = localStorage.getItem('ssh_session');
             if (savedSession) {
                 try {
                     const session = JSON.parse(savedSession);
                     if (session.host && session.username && session.password) {
                         // Restore saved credentials (password is Base64 encoded)
                         $wire.host = session.host;
                         $wire.username = session.username;
                         $wire.password = atob(session.password);
                         
                         // Auto-reconnect
                         await this.connectSSH();
                     }
                 } catch (e) {
                     console.error('Failed to restore session:', e);
                     localStorage.removeItem('ssh_session');
                 }
             }
         },
         
         // Initialize terminal when needed
         async initTerminal() {
             if (this.interactiveTerminal) return;
             
             await $nextTick();
             if (typeof InteractiveTerminal !== 'undefined') {
                 this.interactiveTerminal = new InteractiveTerminal('xterm-container');
                 this.interactiveTerminal.mount();
             } else {
                 console.error('InteractiveTerminal not loaded');
             }
         },
         
         async connectSSH() {
             if (!$wire.host || !$wire.username || !$wire.password) {
                 alert('Please fill in all fields');
                 return;
             }
             
             this.connecting = true;
             
             // Show terminal container FIRST so xterm can measure correctly
             this.terminalVisible = true;
             await $nextTick();
             
             // Initialize terminal after container is visible
             await this.initTerminal();
             await $nextTick();
             
             // Connect
             const success = await this.interactiveTerminal.connect(
                 $wire.host, 
                 $wire.username, 
                 $wire.password
             );
             
             if (success) {
                 $wire.isConnected = true;
                 // Save session with password for auto-reconnect
                 localStorage.setItem('ssh_session', JSON.stringify({
                     host: $wire.host,
                     username: $wire.username,
                     password: btoa($wire.password) // Base64 encode password
                 }));
                 this.interactiveTerminal.focus();
             } else {
                 this.terminalVisible = false;
             }
             
             this.connecting = false;
         },
         
         async disconnect() {
             localStorage.removeItem('ssh_session');
             if (this.interactiveTerminal) {
                 this.interactiveTerminal.dispose();
                 this.interactiveTerminal = null;
             }
             // Clear terminal container
             const container = document.getElementById('xterm-container');
             if (container) {
                 container.innerHTML = '';
             }
             $wire.host = '';
             $wire.username = '';
             $wire.password = '';
             $wire.isConnected = false;
             this.terminalVisible = false;
         }
     }"
>
    <!-- Connection Bar -->
    <div class="bg-gray-800 p-3 rounded mb-4 flex gap-2 flex-wrap" x-data="{ expanded: !$wire.isConnected }">
        <div class="w-full flex justify-between items-center mb-2">
             <span class="text-gray-400 text-sm font-bold flex items-center gap-2">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                 </svg>
                 Remote Terminal
                 <span x-show="$wire.isConnected" class="text-green-400 ml-2">● Connected</span>
             </span>
             <div class="flex gap-2 items-center">
                 <button x-show="$wire.isConnected" 
                         @click="disconnect()" 
                         class="text-xs text-red-400 hover:text-red-300 transition">
                     Disconnect
                 </button>
                 <!-- Theme Toggle -->
                 <button 
                     @click="$store.darkMode.toggle()"
                     class="p-1 rounded hover:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                     :title="$store.darkMode.on ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                 >
                     <svg x-show="$store.darkMode.on" class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                     </svg>
                     <svg x-show="!$store.darkMode.on" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                     </svg>
                 </button>
                 <button @click="expanded = !expanded" class="text-xs text-blue-400 hover:text-blue-300 transition" x-text="expanded ? 'Hide' : 'Show'"></button>
             </div>
        </div>
        
        <div x-show="expanded" x-transition class="w-full flex gap-2 flex-wrap text-sm">
            <input wire:model="host" type="text" placeholder="Host IP" class="bg-gray-700 border border-gray-600 p-2 rounded text-white flex-1 min-w-[140px] focus:border-green-500 focus:outline-none">
            <input wire:model="username" type="text" placeholder="User" class="bg-gray-700 border border-gray-600 p-2 rounded text-white w-24 focus:border-green-500 focus:outline-none">
            <input wire:model="password" type="password" placeholder="Pass" class="bg-gray-700 border border-gray-600 p-2 rounded text-white w-24 focus:border-green-500 focus:outline-none">
            <button @click="connectSSH()" 
                    :disabled="connecting"
                    :class="connecting ? 'bg-gray-600' : 'bg-blue-600 hover:bg-blue-500'"
                    class="px-4 py-2 rounded text-white font-bold transition shadow-lg">
                <span x-show="!connecting">Connect</span>
                <span x-show="connecting">Connecting...</span>
            </button>
        </div>
    </div>

    <!-- Interactive Terminal Container (xterm.js) -->
    <div x-show="terminalVisible" 
         x-transition
         class="flex-1 mb-4 rounded overflow-hidden border border-gray-700 dark:border-gray-700 shadow-lg"
         :class="$store.darkMode.on ? 'bg-[#1a1b26]' : 'bg-[#fafafa]'"
         :style="'--terminal-bg: ' + ($store.darkMode.on ? '#1a1b26' : '#fafafa')"
    >
        <div id="xterm-container" class="h-full w-full" style="min-height: 500px;"></div>
        <style>
            #xterm-container .xterm-viewport,
            #xterm-container .xterm-screen {
                background-color: var(--terminal-bg) !important;
            }
            #xterm-container {
                padding-left: 12px;
                padding-top: 8px;
            }
        </style>
    </div>

    <!-- Welcome Message (when not connected) -->
    <div x-show="!terminalVisible" 
         class="flex-1 bg-gray-900 rounded p-8 mb-4 flex flex-col items-center justify-center text-center border border-gray-800"
    >
        <svg class="w-24 h-24 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h2 class="text-2xl font-bold text-white mb-2">Remote Terminal</h2>
        <p class="text-gray-400 mb-4">Connect to your SSH server to start</p>
        <p class="text-gray-600 text-xs mt-4">Developed by Rahman Umardi</p>
    </div>
</div>
