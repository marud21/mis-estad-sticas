@props(['items'])
<nav class="breadcrumbs" aria-label="breadcrumb">
    <a href="{{ route('socios.index') }}">Inicio</a>
    @foreach ($items as $label => $url)
        <span class="breadcrumb-sep">/</span>
        @if ($url)
            <a href="{{ $url }}">{{ $label }}</a>
        @else
            <span class="breadcrumb-current">{{ $label }}</span>
        @endif
    @endforeach
</nav>
