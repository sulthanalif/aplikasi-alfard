<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach ($stats as $stat)
        @if (isset($stat['can']) && $stat['can'])
            @hasrole($stat['can'])
                <x-stat
                    title="{{ $stat['title'] }}"
                    value="{{ $stat['value'] }}"
                    icon="{{ $stat['icon'] }}"
                    {{-- tooltip="{{ $tooltip }}" --}}
                    color="{{ $stat['color'] }}"
                />
            @endhasrole
        @else
            <x-stat
                title="{{ $stat['title'] }}"
                value="{{ $stat['value'] }}"
                icon="{{ $stat['icon'] }}"
                {{-- tooltip="{{ $tooltip }}" --}}
                color="{{ $stat['color'] }}"
            />
        @endif
    @endforeach
</div>
