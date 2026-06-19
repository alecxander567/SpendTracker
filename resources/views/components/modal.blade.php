@props([
    'id' => 'defaultModal',
    'title' => '',
    'icon' => null,
    'iconColor' => 'primary',
    'buttonText' => 'Close',
    'buttonClass' => 'btn-primary',
    'size' => 'modal-md',
    'centered' => true,
    'scrollable' => false,
    'staticBackdrop' => false,
    'showCloseButton' => true,
    'closeOnEscape' => true,
    'closeOnBackdrop' => true,
    'redirectUrl' => null, 
])

@php
    $backdropClass = $staticBackdrop ? 'modal-static' : '';
    $centeredClass = $centered ? 'modal-dialog-centered' : '';
    $scrollableClass = $scrollable ? 'modal-dialog-scrollable' : '';
    $dataBackdrop = $staticBackdrop ? 'static' : 'true';
    $dataKeyboard = $closeOnEscape ? 'true' : 'false';
    $dataBackdropClose = $closeOnBackdrop ? 'true' : 'static';
@endphp

<!-- Modal -->
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true"
    data-bs-backdrop="{{ $dataBackdropClose }}" data-bs-keyboard="{{ $dataKeyboard }}">
    <div class="modal-dialog {{ $size }} {{ $centeredClass }} {{ $scrollableClass }}">
        <div class="modal-content shadow-lg border-0">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0">
                @if (!empty($title))
                    <h5 class="modal-title fw-bold" id="{{ $id }}Label">
                        @if ($icon)
                            <i class="fas fa-{{ $icon }} text-{{ $iconColor }} me-2"></i>
                        @endif
                        {{ $title }}
                    </h5>
                @endif
                @if ($showCloseButton)
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                @endif
            </div>

            <!-- Modal Body -->
            <div class="modal-body py-4">
                {{ $slot }}
            </div>

            <!-- Modal Footer -->
            @isset($footer)
                <div class="modal-footer border-0 pt-0">
                    {{ $footer }}
                </div>
            @else
                <div class="modal-footer border-0 pt-0">
                    @if ($redirectUrl)
                        <a href="{{ $redirectUrl }}" class="btn {{ $buttonClass }}">
                            {{ $buttonText }}
                        </a>
                    @else
                        <button type="button" class="btn {{ $buttonClass }}" data-bs-dismiss="modal">
                            {{ $buttonText }}
                        </button>
                    @endif
                </div>
            @endisset
        </div>
    </div>
</div>
