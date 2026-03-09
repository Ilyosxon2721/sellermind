{{--
    PWA Message Bubble Component
    Chat bubble for AI Assistant conversation

    @props
    - message: string - Message text content
    - type: string - user/assistant/system
    - timestamp: string - Message time
    - status: string - sent/delivered/read (for user messages)
    - typing: bool - Show typing indicator instead of message
--}}

@props([
    'message' => '',
    'type' => 'user',
    'timestamp' => null,
    'status' => 'sent',
    'typing' => false,
])

@php
$isUser = $type === 'user';
$isAssistant = $type === 'assistant';
$isSystem = $type === 'system';

// Container alignment
$containerClass = match($type) {
    'user' => 'flex justify-end',
    'assistant' => 'flex justify-start',
    'system' => 'flex justify-center',
    default => 'flex justify-start',
};

// Bubble styling
$bubbleClass = match($type) {
    'user' => 'bg-blue-600 text-white rounded-2xl rounded-br-md max-w-[80%]',
    'assistant' => 'bg-white border border-gray-200 text-gray-900 rounded-2xl rounded-bl-md max-w-[85%]',
    'system' => 'bg-gray-100 text-gray-600 rounded-xl max-w-[90%] text-center',
    default => 'bg-white border border-gray-200 text-gray-900 rounded-2xl rounded-bl-md max-w-[85%]',
};

// Status icons
$statusIcon = match($status) {
    'sent' => '<svg class="w-3.5 h-3.5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    'delivered' => '<svg class="w-3.5 h-3.5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7M5 7l4 4"/></svg>',
    'read' => '<svg class="w-3.5 h-3.5 text-blue-400" fill="currentColor" viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>',
    default => '',
};
@endphp

<div class="{{ $containerClass }}">
    @if($typing)
        {{-- Typing Indicator --}}
        <div class="bg-white border border-gray-200 rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
            <div class="flex items-center space-x-1">
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms; animation-duration: 1s;"></span>
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms; animation-duration: 1s;"></span>
                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms; animation-duration: 1s;"></span>
            </div>
        </div>
    @else
        <div class="flex flex-col {{ $isUser ? 'items-end' : 'items-start' }}">
            {{-- Message Bubble --}}
            <div class="{{ $bubbleClass }} px-4 py-2.5 shadow-sm">
                @if($isAssistant && !$isSystem)
                    {{-- Assistant avatar indicator --}}
                    <div class="flex items-start space-x-2">
                        <span class="text-base flex-shrink-0 mt-0.5">&#129302;</span>
                        <div
                            x-data="{ content: @js($message) }"
                            x-init="$nextTick(() => {
                                // Simple markdown rendering for assistant messages
                                const text = content
                                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                                    .replace(/`(.*?)`/g, '<code class=\"bg-gray-100 px-1 py-0.5 rounded text-sm font-mono\">$1</code>')
                                    .replace(/\n/g, '<br>');
                                $el.innerHTML = text;
                            })"
                            class="text-[15px] leading-relaxed prose prose-sm max-w-none"
                        >
                            {{ $message }}
                        </div>
                    </div>
                @elseif($isSystem)
                    <p class="text-[13px] leading-relaxed">{{ $message }}</p>
                @else
                    <p class="text-[15px] leading-relaxed whitespace-pre-wrap break-words">{{ $message }}</p>
                @endif
            </div>

            {{-- Timestamp and Status --}}
            @if($timestamp)
                <div class="flex items-center space-x-1 mt-1 px-1">
                    <span class="text-[11px] text-gray-400">{{ $timestamp }}</span>
                    @if($isUser && $status)
                        <span class="flex-shrink-0">{!! $statusIcon !!}</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

<style>
/* Typing indicator animation */
@keyframes typing-bounce {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-4px);
    }
}

.pwa-mode .animate-bounce {
    animation: typing-bounce 1s infinite;
}

/* Smooth message appearance */
.pwa-mode [class*="message-bubble"] {
    animation: messageAppear 0.2s ease-out;
}

@keyframes messageAppear {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Code styling in assistant messages */
.pwa-mode .prose code {
    background: rgba(0, 0, 0, 0.05);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 13px;
    font-family: ui-monospace, monospace;
}

/* Links in assistant messages */
.pwa-mode .prose a {
    color: #007AFF;
    text-decoration: underline;
}
</style>
