<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use Toast, WithPagination;

    public string $start_date, $end_date;
    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'asc'];
    public int $perPage = 10;

    public function mount()
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->toDateString();
    }

    public function datas(): LengthAwarePaginator
    {
        return Sales::whereBetween('created_at', [$this->start_date, $this->end_date])
            ->when($this->search, function ($query) {
                $query->where('invoice', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }
}; ?>

<div>
    //
</div>
