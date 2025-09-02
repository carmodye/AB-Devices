<?php

namespace App\Livewire;

use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateNote extends Component
{
    #[Rule('required')]
    public $title = '';

    #[Rule('required')]
    public $content = '';

    public function save()
    {
        $this->validate();

        Auth::user()->notes()->create(
            $this->only(['title', 'content'])
        );

        return redirect()->to('/notes');
    }

    public function render()
    {
        return view('livewire.pages.notes.create-note')
            ->layout('layouts.app');
    }
}
