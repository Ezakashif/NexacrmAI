@props(['user', 'size' => 32, 'class' => ''])

<img src="{{ $user->photoUrl() }}"
     alt="{{ $user->name }}"
     class="img-circle elevation-1 {{ $class }}"
     style="width: {{ $size }}px; height: {{ $size }}px; object-fit: cover;"
     {{ $attributes }}>
