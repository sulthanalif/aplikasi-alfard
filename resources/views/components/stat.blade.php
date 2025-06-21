<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($stats as $stat)
        <x-stat
        title="{{ $stat['title'] }}"
        value="{{ $stat['value'] }}"
        icon="{{ $stat['icon'] }}"
        {{-- tooltip="{{ $tooltip }}" --}}
        color="{{ $stat['color'] }}" />
    @endforeach
</div>
