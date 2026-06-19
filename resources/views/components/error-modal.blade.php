@props([
    'id' => 'errorModal',
    'title' => 'Error!',
    'message' => 'Something went wrong.',
    'icon' => 'exclamation-circle',
    'iconColor' => 'danger',
    'buttonText' => 'Close',
    'buttonClass' => 'btn-danger',
    'showButton' => true,
    'errors' => [],
])

<div>
    <x-modal :id="$id" :title="$title" :icon="$icon" :iconColor="$iconColor" :buttonText="$buttonText" :buttonClass="$buttonClass"
        :centered="true">
        <div class="text-center py-3">
            <div class="mb-4">
                <i class="fas fa-{{ $icon }} text-{{ $iconColor }}" style="font-size: 64px;"></i>
            </div>
            <p class="lead mb-3">{{ $message }}</p>

            @if (!empty($errors))
                <div class="text-start mt-3">
                    <ul class="list-unstyled mb-0">
                        @foreach ($errors as $error)
                            <li class="text-danger small">
                                <i class="fas fa-times-circle me-1"></i>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-modal>
</div>
