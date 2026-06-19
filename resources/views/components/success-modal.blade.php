@props([
    'id' => 'successModal',
    'title' => 'Success!',
    'message' => 'Operation completed successfully.',
    'icon' => 'check-circle',
    'iconColor' => 'success',
    'buttonText' => 'Continue',
    'buttonClass' => 'btn-success',
    'showButton' => true,
    'redirectUrl' => null,
])

<div>
    <x-modal :id="$id" :title="$title" :icon="$icon" :iconColor="$iconColor" :buttonText="$buttonText" :buttonClass="$buttonClass"
        :centered="true" :redirectUrl="$redirectUrl">
        <div class="text-center py-3">
            <div class="mb-4">
                <i class="fas fa-{{ $icon }} text-{{ $iconColor }}" style="font-size: 64px;"></i>
            </div>
            <p class="lead mb-0">{{ $message }}</p>
        </div>
    </x-modal>
</div>
