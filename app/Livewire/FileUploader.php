<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class FileUploader extends Component
{
    use WithFileUploads;

    public $files = [];

    public function updatedFiles()
    {
        foreach ($this->files as $file) {
            $file->store('uploads', 'public');
        }

        session()->flash('success', 'Files uploaded successfully.');
    }

    public function render()
    {
        return view('livewire.file-uploader')
            ->layout('layouts.app');
    }
}
