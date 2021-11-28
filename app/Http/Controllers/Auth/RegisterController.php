<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\UserRegistered;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use LVR\Phone\Phone;
use Validator;

use Illuminate\Support\Facades\Valid3ator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
//            'name' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:30'],
            'fname' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:19'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $name = $data['lname'] . ' ' . $data['fname'] . ' ' . $data['mname'];
        validator::make($data,
            [
                'phone' => new Phone
            ]
        )->validate();
        $phone = preg_replace('/[\+\(\)\-\s]/', '', $data['phone']);

        $user = User::create([
            'name' => $name,
            'lname' => $data['lname'],
            'fname' => $data['fname'],
            'mname' => $data['mname'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'note' => $data['note'],
            'phone' => $phone,
        ]);

        //Инициируем событие о регистрации пользователя
        event(new UserRegistered($user));

        return $user;

    }
}
