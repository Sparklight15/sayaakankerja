<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <a href="{{ route('dashboard') }}" wire:navigate class="font-semibold text-gray-800">
            Saya Akan Kerja
        </a>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">{{ auth()->user()->username }}</span>
            <button wire:click="logout" class="text-sm text-gray-600 hover:text-gray-900 underline">
                Logout
            </button>
        </div>
    </div>
</nav>