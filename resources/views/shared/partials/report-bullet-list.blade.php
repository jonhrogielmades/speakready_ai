@php
    $items = array_values(array_filter($items ?? [], fn ($item) => is_scalar($item) && trim((string) $item) !== ''));
    $icon = $icon ?? 'fa-circle-check';
    $color = $color ?? '#10b981';
@endphp

<ul class="list-unstyled d-flex flex-column gap-2 mb-0" style="color:var(--tx);line-height:1.58;">
    @foreach($items as $item)
        <li class="d-flex gap-2">
            <i class="fa-solid {{ $icon }} mt-1" style="color:{{ $color }};font-size:.78rem;"></i>
            <span>{{ $item }}</span>
        </li>
    @endforeach
</ul>
