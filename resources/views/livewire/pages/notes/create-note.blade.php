<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Create Note') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ __("Add a note and it will be saved for you.") }}
                    </p>
                </header>

                <form wire:submit.prevent="save" class="mt-6 space-y-6">
                    <div>
                        <x-label for="title" :value="__('Title')" />
                        {{-- <x-input wire:model="title" id="title" name="title" type="text" class="mt-1 block w-full"
                            required autofocus autocomplete="title" />
                        --}}
                        <x-input id="title" type="text" class="mt-1 block w-full" wire:model="title" autofocus />

                        <x-input-error for="title" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="content" :value="__('Content')" />
                        {{-- <x-input wire:model="content" id="content" name="content" type="text"
                            class="mt-1 block w-full" required autocomplete="content" />
                        --}}
                        <x-input wire:model="content" id="content" type="text" class="mt-1 block w-full" />

                        <x-input-error for="content" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-button>{{ __('Save') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>