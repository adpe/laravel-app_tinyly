<?php

namespace App\Http\Livewire;

use Auth;
use Livewire\Component;

class LoginForm extends Component
{
    public $email;
    public $password;
    public $remember;

    protected function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function render()
    {
        return view('livewire.login-form');
    }

    public function submit()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->flash('success_message', 'Login successfully.');

            return redirect()->to('/links');
        }

        session()->put('email', $this->email);
        session()->put('password', $this->password);
        session()->put('remember', $this->remember);

        session()->flash('error_message', 'Login failed.');

        return redirect()->to('/login');
    }

    public function mount()
    {
        $this->email = session()->get('email');
        $this->password = session()->get('password');
        $this->remember = session()->get('remember');

        session()->remove('email');
        session()->remove('password');
        session()->remove('remember');
    }
}
