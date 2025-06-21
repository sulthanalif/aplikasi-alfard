<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.guest')] class extends Component {
    public function mount(): void
    {
        if (auth()->user()->status) {
            redirect()->route('dashboard');
        }
    }

    public function logout(): void
    {
        $this->redirect(route('logout'), navigate: true);
    }
}; ?>

<div class="flex items-center justify-center min-h-screen h-screen">
    <div class="w-full max-w-md">
        <div class="rounded-lg shadow p-6 bg-base-100">
            <div class="flex w-full justify-center">
                <x-app-brand class="mb-4"  />
            </div>
            <!-- <div class="font-bold text-2xl text-center">
                <p>Login</p>
            </div> -->
            <div class="mt-6">
                <p class="text-center">Your account is inactive. Please contact the administrator to activate your account.</p>
                <div class="flex justify-center mt-6">
                    <x-button label="Logout" wire:click="logout" spinner="logout" class="btn-primary" />
                </div>
            </div>
        </div>
    </div>
</div>
