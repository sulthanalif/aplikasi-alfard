

@php
    $menuItems = config('menu');
    $user_new = App\Models\User::where('is_new', 1)->count();

    function resolve_link($link)
    {
        try {
            return route($link);
        } catch (Exception $e) {
            return '#';
        }
    }

    function can_access($can)
    {
        return is_null($can) || auth()->user()?->can($can);
    }
@endphp

@foreach ($menuItems as $item)
    @if (can_access($item['can'] ?? null))
        @if ($item['type'] === 'sub')
            <x-menu-sub title="{{ $item['title'] }}" icon-classes="{{ $item['icon-classes'] ?? '' }}" icon="{{ $item['icon'] }}">
                @foreach ($item['submenu'] ?? [] as $subItem)
                    @if (can_access($subItem['can'] ?? null))
                        <x-menu-item
                            title="{{ $subItem['title'] }}"
                            icon="{{ $subItem['icon'] }}"
                            icon-classes="{{ $subItem['icon-classes'] ?? '' }}"
                            link="{{ resolve_link($subItem['link']) }}"
                        />
                    @endif
                @endforeach
            </x-menu-sub>
        @elseif ($item['type'] === 'item')
            <x-menu-item
                title="{{ $item['title'] }}"
                icon="{{ $item['icon'] }}"
                badge="{{ $item['title'] == 'Customers' ? $user_new : '' }}"
                badge-classes="badge-soft badge-error"
                icon-classes="{{ $item['icon-classes'] ?? '' }}"
                link="{{ resolve_link($item['link']) }}"
            />
        @endif
    @endif
@endforeach
