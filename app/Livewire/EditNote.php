<?php

namespace App\Livewire;

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

class EditNote extends Component
{
    public Note $note;

    #[Rule('required')]
    public $title = '';

    #[Rule('required')]
    public $content = '';

    public function mount(Note $note)
    {
        $this->note = $note;

        $this->title = $note->title;

        $this->content = $note->content;
    }

    public function save()
    {
        $this->note->update(
            $this->all()
        );
        return redirect()->to('/notes');
    }

    public function render()
    {
        return view('livewire.pages.notes.edit-note')
            ->layout('layouts.app');
    }
}
