<x-modal wire:model="modal" title="Form Unit" box-class="w-full h-fit max-w-[800px]" without-trap-focus>
    <x-form wire:submit="save" no-separator>

        <div>
            <x-input label="Name" wire:model="name" required />
        </div>

        <div>
            <x-textarea label="Description" wire:model="description" rows="3" />
        </div>

        <div class="mt-4">
            <x-toggle label="Status" wire:model="status" hint="If checked, is active" />
        </div>

        <x-slot:actions>
            @if ($this->recordId)
                <x-button label="Delete" wire:click="delete" class="btn-error" wire:confirm="Are you sure?" />
            @endif
            <x-button label="save" type="submit" spinner="save" class="btn-primary" />
        </x-slot:actions>
    </x-form>
</x-modal>
