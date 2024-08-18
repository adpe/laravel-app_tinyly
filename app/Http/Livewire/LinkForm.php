<?php

namespace App\Http\Livewire;

use App\Http\Controllers\ShortLinkController;
use App\Models\ShortLink;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Component;

class LinkForm extends Component
{
    public $link;
    public $slug;
    public $code;
    public $message;
    public $text;
    public $method;

    protected function rules()
    {
        return [
            'url' => 'required|url',
            'code' =>
                'required|unique:short_links,code,' . $this->link->id,
        ];
    }

    public function render()
    {
        return view('livewire.link-form');
    }

    public function submit()
    {
        $this->validate();

        $this->link->link = $this->url;
        $this->link->code = $this->code;

        $controller = new ShortLinkController();

        if ($this->method == 'PATCH') {
            $controller->update($this->link);

            session()->flash('success_message', 'Link successfully updated.');

            return redirect()->to('/links');
        }

        $controller->store($this->link);

        session()->flash('success_message', 'Link successfully created.');

        return redirect()->to('/links');
    }

    public function mount(string $text, ShortLink $link)
    {
        $this->text = $text;
        $this->link = $link;
        $this->url = $link->link;
        $this->code = $link->code;

        $route = Route::currentRouteAction();
        if (Str::endsWith($route, 'edit')) {
            $this->method = 'PATCH';
        }
    }
}
