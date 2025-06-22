<?php

use App\Models\User;
use Mary\Traits\Toast;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.guest')] #[Title('Register')] class extends Component {
    use Toast, LogFormatter;

    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $name = '';
    public string $address = '';
    public string $phone = '';

    public function register(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password_confirmation' => 'required',
            'password' => 'required|confirmed',
            'name' => 'required',
            'address' => 'required|max:500',
            'phone' => 'required|numeric|min:13',
        ]);

        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'address' => $this->address,
                'phone' => $this->phone,
                'status' => false,
                'is_new' => true,
            ]);

            $user->assignRole('customer');

            DB::commit();
            $this->success('Register Success', position: 'toast-bottom', redirectTo: route('login'));
        } catch (\Exception $th) {
            DB::rollBack();
            $this->logError($th);
            $this->error('Register Failed', position: 'toast-bottom');
        }
    }
}; ?>

<div class="flex items-center justify-center py-4">
    <div class="w-full max-w-[700px]">
        <div class="rounded-lg shadow p-6 bg-base-100">
            <div class="flex w-full justify-center">
                <x-app-brand class="mb-4"  />
            </div>
            <div class="font-bold text-2xl text-center">
                <p>Register</p>
            </div>
            <div class="mt-6">
                <x-form wire:submit="register">
                    <x-input label="Nama" type="text" wire:model="name"   autofocus  required/>
                    <x-textarea label="Alamat" wire:model="address" required/>
                    <x-input label="Email" type="email" wire:model="email" required/>
                    <x-password label="Password" type="password" wire:model="password"  right required/>
                    <x-password label="Konfirmasi Password" type="password" wire:model="password_confirmation"  right required/>
                    <x-input label="Nomor Telepon" type="text" wire:model="phone" required/>

                    <div class="flex justify-between items-center my-3">
                        <a class=" cursor-pointer hover:underline" wire:navigate href="{{ route('login') }}"
                            wire:navigate>Already have an account?</a>
                    </div>
                    <x-slot:actions>
                        <x-button label="Register" class="btn-primary" type="submit" spinner="register" />
                    </x-slot:actions>
                </x-form>
            </div>
        </div>
    </div>
</div>
