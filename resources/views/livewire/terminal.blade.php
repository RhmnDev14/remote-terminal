<div class="flex flex-col h-screen max-w-4xl mx-auto p-2">
    <!-- Connection Bar -->
    <div class="bg-gray-800 p-3 rounded mb-4 flex gap-2 flex-wrap" x-data="{ expanded: !$wire.isConnected }">
        <div class="w-full flex justify-between items-center mb-2">
             <span class="text-gray-400 text-sm font-bold">SSH Connection</span>
             <button @click="expanded = !expanded" class="text-xs text-blue-400 hover:text-blue-300 transition" x-text="expanded ? 'Hide Monitor' : 'Show Monitor'"></button>
        </div>
        
        <div x-show="expanded" x-transition class="w-full flex gap-2 flex-wrap text-sm">
            <input wire:model="host" type="text" placeholder="Host IP" class="bg-gray-700 border border-gray-600 p-2 rounded text-white flex-1 min-w-[140px] focus:border-green-500 focus:outline-none">
            <input wire:model="username" type="text" placeholder="User" class="bg-gray-700 border border-gray-600 p-2 rounded text-white w-24 focus:border-green-500 focus:outline-none">
            <input wire:model="password" type="password" placeholder="Pass" class="bg-gray-700 border border-gray-600 p-2 rounded text-white w-24 focus:border-green-500 focus:outline-none">
            <button wire:click="testConnection" class="bg-blue-600 px-4 py-2 rounded text-white font-bold hover:bg-blue-500 transition shadow-lg">Connect</button>
        </div>
    </div>

    <!-- Terminal Output -->
    <div class="flex-1 bg-black rounded p-4 overflow-y-auto mb-4 font-mono text-sm shadow-inner shadow-gray-800 border border-gray-800"
         id="terminal-output"
         x-data
         x-init="$watch('$wire.history', () => { $nextTick(() => $el.scrollTo(0, $el.scrollHeight)) })"
    >
        @foreach($history as $entry)
            <div class="mb-1 break-words">
                @if($entry['type'] === 'command')
                    <span class="text-green-500 font-bold">{{ $entry['content'] }}</span>
                @elseif($entry['type'] === 'error')
                    <span class="text-red-500">{{ $entry['content'] }}</span>
                @elseif($entry['type'] === 'success')
                    <span class="text-blue-400 font-bold">✓ {{ $entry['content'] }}</span>
                @else
                    <span class="text-gray-300 whitespace-pre-wrap">{{ $entry['content'] }}</span>
                @endif
            </div>
        @endforeach
        
        <!-- Loading Indicator -->
        <div wire:loading wire:target="runCommand" class="text-gray-500 italic">Executing...</div>
        <div wire:loading wire:target="testConnection" class="text-gray-500 italic">Connecting...</div>
    </div>

    <!-- Command Input -->
    <form wire:submit.prevent="runCommand" class="flex gap-2 bg-gray-900 pb-2">
        <span class="text-green-500 self-center font-bold text-xl">$</span>
        <input wire:model="command" type="text" 
               class="flex-1 bg-gray-800 text-white p-3 rounded border border-gray-700 focus:border-green-500 focus:ring-0 font-mono transition"
               placeholder="Enter command..."
               autofocus
        >
    </form>
</div>
