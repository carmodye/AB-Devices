<div x-data="fileUploadComponent" x-init="init()" class="max-w-xl mx-auto p-4 bg-white rounded shadow">
    <div x-ref="dropzone" @dragover.prevent @drop.prevent="handleDrop($event)"
        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer bg-gray-50 hover:bg-gray-100">
        <input type="file" multiple wire:model="files" x-ref="fileInput" class="hidden" />
        <p class="text-gray-500">Drag & drop files or <button type="button" @click="$refs.fileInput.click()"
                class="text-indigo-600 underline">browse</button></p>
    </div>

    <template x-for="(file, index) in previews" :key="index">
        <div class="mt-4 flex items-center space-x-4">
            <img :src="file.preview" class="w-16 h-16 object-cover rounded border" />
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-700" x-text="file.name"></p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: file.progress + '%' }"></div>
                </div>
            </div>
        </div>
    </template>

    @if (session()->has('success'))
        <div class="mt-4 text-green-600">{{ session('success') }}</div>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('fileUploadComponent', () => ({
            previews: [],
            init() {
                this.previews = [];
            },
            handleDrop(event) {
                const files = event.dataTransfer.files;
                this.handleFiles(files);
                document.querySelector('[wire\\:model="files"]').files = files;
                Livewire.find(document.querySelector('[wire\\:model="files"]').closest('[wire\\:id]').getAttribute('wire:id')).upload('files', files, () => { }, () => { }, (progress) => {
                    this.previews.forEach(p => p.progress = progress);
                });
            },
            handleFiles(fileList) {
                Array.from(fileList).forEach(file => {
                    const reader = new FileReader();
                    const fileObj = {
                        name: file.name,
                        progress: 0,
                        preview: null
                    };

                    if (file.type.startsWith('image/')) {
                        reader.onload = e => {
                            fileObj.preview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }

                    this.previews.push(fileObj);
                });
            }
        }));
    });
</script>