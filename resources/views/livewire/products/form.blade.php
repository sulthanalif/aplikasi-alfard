<x-modal wire:model="modal" title="Form Product" box-class="w-full h-fit max-w-[800px]" without-trap-focus>
    <x-form wire:submit="save" no-separator>

        <div>
            <x-input label="Code" wire:model="code"  />
        </div>

        <div>
            <x-input label="Name" wire:model="name"  />
        </div>

        <div>
            <x-choices-offline
            label="Category"
            wire:model="category_id"
            :options="$categoriesSearchable"
            placeholder="Search ..."
            search-function="searchCategory"
            single
            searchable />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <x-input label="Price" wire:model="price" prefix="Rp"   />
            </div>
            <div>
                <x-input label="Stock" wire:model="stock" type="number"  />
            </div>
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
