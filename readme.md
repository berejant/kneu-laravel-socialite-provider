# KNEU Laravel Socialite

KNEU Provider for [Laravel Socialite Providers](https://socialiteproviders.github.io/).

## Installation

### 1. Composer

Add package to your laravel project via composer

    composer require kneu/laravel-socialite-provider

### 2. Add service provider

* Remove `Laravel\Socialite\SocialiteServiceProvider` from your `providers[]` array in `config\app.php` if you have added it already.
* Add `\SocialiteProviders\Manager\ServiceProvider::class` to your `providers[]` array in `config\app.php`.

For example:
```php
    'providers' => [
        ...
        // remove 'Laravel\Socialite\SocialiteServiceProvider',
        \SocialiteProviders\Manager\ServiceProvider::class, // add
    ];
```

### 3. Add the Event and Listeners

* Add `SocialiteProviders\Manager\SocialiteWasCalled` event to your `listen[]` array  in `<app_name>/Providers/EventServiceProvider`.

* Add listener `'\SocialiteProviders\Kneu\KneuExtendSocialite@handle'` to the `SocialiteProviders\Manager\SocialiteWasCalled[]`.


For example:
``` php
    protected $listen = [
        ...
        \SocialiteProviders\Manager\SocialiteWasCalled::class  => [
            ...
            '\SocialiteProviders\Kneu\KneuExtendSocialite@handle',
        ],
    ];
```

### 4. Environment Variables

Append provider values to your `.env` file

```
KNEU_KEY=your_application_id
KNEU_SECRET=your_secret
KNEU_REDIRECT_URI=https://example.com/login/complete
```


## Basic Usage

Next, you are ready to authenticate users via KNEU! You will need tree routes:
* first for redirecting the user to the KNEU OAuth provider
* second for receiving the callback from the KNEU provider after authentication
* third for logout.

Example of Controller
```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\User;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\OAuth2\User as KneuUser;

class LoginController extends Controller
{
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * @return \SocialiteProviders\Kneu\Provider
     */
    protected function getProvider()
    {
        return Socialite::with('kneu');
    }

    public function login(Request $request)
    {
        $request->session()->put('url.intended', url()->previous());

        return $this->getProvider()->redirect();
    }

    public function loginComplete()
    {
        /** @var KneuUser $kneuUser */
        $kneuUser = $this->getProvider()->user();

        $user = User::withTrashed()->find($kneuUser->id);
        if(!$user) {
            $user = new User();
        }

        $user->fill($kneuUser->getRaw());
        $user->trashed() ? $user->restore() : $user->touch();

        Auth::login($user);

        return redirect()->intended($this->redirectTo);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return $this->getProvider()->logoutRedirect(url()->previous());
    }

}

```

Of course, you will need to define routes to your controller methods:

```php
Route::get('/login', 'Auth\LoginController@login');
Route::get('/login/complete', 'Auth\LoginController@loginComplete');
Route::get('/logout', 'Auth\LoginController@logout');
```

### Retrieving User Details

Once you have a user instance, you can grab a few more details about the user:

```php
$user = Socialite::driver('github')->user();

// OAuth Two Providers
$token = $user->token;
$refreshToken = $user->refreshToken; // currently not provided by auth.kneu.edu.ua
$expiresIn = $user->expiresIn;

$user->id;
$user->name;
$user->email;
$user->type; // enum ['student', 'teacher', 'simple']
$user->first_name;
$user->middle_name;
$user->last_name;
$user->teacher_id;
$user->department_id;
$user->student_id;
$user->group_id;
$user->sex; // only for student, but not always correct
```

### More details

See documentation for [Laravel Socialite Providers](https://socialiteproviders.github.io/).
