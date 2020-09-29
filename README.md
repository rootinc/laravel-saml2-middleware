# Laravel Saml2 Middleware

Provides Saml2 Authentication Middleware for a Laravel App.  If you like this, checkout <a href="https://github.com/rootinc/laravel-azure-middleware">Laravel Azure Middleware</a>

## Normal Installation

1. `composer require rootinc/laravel-saml2-middleware`
2. run `php artisan vendor:publish --provider="RootInc\LaravelSaml2Middleware\Saml2ServiceProvider"` to install config file to `config/saml2.php`
3. In our routes folder (most likely `web.php`), add
```php
Route::get('/login/saml2', '\RootInc\LaravelSaml2Middleware\Saml2@saml2');
Route::post('/login/saml2callback', '\RootInc\LaravelSaml2Middleware\Saml2@saml2callback');
```

4. In our `App\Http\Kernel.php` add `'saml2' => \RootInc\LaravelSaml2Middleware\Saml2::class,` most likely to the `$routeMiddleware` array.
5. In our `.env` optionally add `SAML2_STRICT, SAML2_SAML2_PROXY_VARS`.  If not added, these values will default to true.
6. In our `.env` add `SAML2_IDP_ENTITYID, SAML2_IDP_SSO, SAML2_IDP_SLO and SAML2_IDP_x509`.
7. In our `.env` optionally add `SAML2_SP_NAME_ID_FORMAT, SAML2_SP_ENTITY_ID, SAML2_SP_SSO, SAML2_SP_SLO`.  These values are only required to override if the default config does not suffice.
8. In our `App\Http\Middleware\VerifyCsrfToken.php` add `'/login/saml2callback' //original saml2 didn't protect anything.  Since this is a POST for SAML2, the tokens will of course not match.  Thus, we need to ignore` to the `$except` array.
9. Add the `saml2` middleware to your route groups on any routes that needs protected by auth and enjoy :tada:
10. If you need custom callbacks, see [Extended Installation](#extended-installation).

## Routing

`Route::get('/login/saml2', '\RootInc\LaravelSaml2Middleware\Saml2@saml2');` First parameter can be wherever you want to route the saml2 login.  * Change as you would like.

`Route::post('/login/saml2callback', '\RootInc\LaravelSaml2Middleware\Saml2@saml2callback');` First parameter can be whatever you want to route after your callback.  * Change as you would like.

`Route::get('/logout/saml2', '\RootInc\LaravelSaml2Middleware\Saml2@saml2logout');` First parameter can be whatever you want to route after your callback.  * Change as you would like.

`Route::post('/logout/logoutcallback', '\RootInc\LaravelSaml2Middleware\Saml2@logoutcallback');` First parameter can be whatever you want to route after your callback.  * Change as you would like.

* Note - if we change these values, it is important to see [Service Provider Options Override](#service-provider-options-override)

## Metadata

As of of v0.2.0, we added the ability to get the metadata.  Simply add:

`Route::get('/saml2/metadata', '\RootInc\LaravelSaml2Middleware\Saml2@saml2metadata');` First parameter can be whatever you want to route for the metadata.  * Change as you would like.

* Note - if we change these values, it is important to see [Service Provider Options Override](#service-provider-options-override)

## Extended Installation

The out-of-the-box implementation let's you login users.  However, let's say we would like to store this user into a database, as well as login the user in with Laravel Auth.  There are two callbacks that are recommended to extend from the Saml2 class called `success` and `fail`. The following provides information on how to extend the Root Laravel Saml2 Middleware Library:

1. To get started (assuming we've followed the [Normal Installation](#normal-installation) directions), create a file called `AppSaml2.php` in the `App\Http\Middleware` folder.  You can either do this through `artisan` or manually.
2. Add this as a starting point in this file:

```php
<?php

namespace App\Http\Middleware;

use RootInc\LaravelSaml2Middleware\Saml2 as Saml2;

use Auth;

use App\User;

class AppSaml2 extends Saml2
{
    protected function success($request, $token, $profile)
    {
        $email = mb_strtolower($profile['Email'][0]);

        $user = User::updateOrCreate(['email' => $email], [
            'firstName' => $profile['FirstName'][0],
            'lastName' => $profile['LastName'][0],
        ]);

        Auth::login($user, true);

        return parent::success($request, $token, $profile);
    }
}
```

The above gives us a way to add/update users after a successful handshake.  `$profile` contains all sorts of metadata that we use to create or update our user.  The default implementation redirects to the intended url, or `/`, so we call the parent here.  Feel free to not extend the default and to redirect elsewhere.

3. Our routes need to be updated to the following:

```php
Route::get('/login/saml2', '\App\Http\Middleware\AppSaml2@saml2');
Route::post('/login/saml2callback', '\App\Http\Middleware\AppSaml2@saml2callback');
Route::get('/logout/saml2', '\App\Http\Middleware\AppSaml2@saml2logout');
Route::post('/logout/logoutcallback', '\App\Http\Middleware\AppSaml2@logoutcallback');
```

As of v0.2.0, if using the metadata route, we'll want to update to be:
`Route::get('/saml2/metadata', '\App\Http\Middleware\AppSaml2@saml2metadata');`

4. Finally, update `Kernel.php`'s `saml2` key to be `'saml2' => \App\Http\Middleware\AppSaml2::class,`

## Service Provider Options Override

As of v0.2.0, we added options for overriding the default behavior for the service provider.  The defaults should generally work well for our app.  However, configuration is always beneficial.  Here are those keys and their default values:

* `SAML2_SP_NAME_ID_FORMAT` defaults to `urn:oasis:names:tc:SAML:2.0:nameid-format:persistent`
* `SAML2_SP_ENTITY_ID` defaults to `url("/saml2/metadata")`
* `SAML2_SP_SSO` defaults to `url("/login/saml2callback")`
* `SAML2_SP_SLO` defaults to `url("logout/saml2callback")`

It's important that if we are not following the naming conventions of the readme, that we update these `SP` values.

## Other Extending Options

#### Callback on Every Handshake

A callback after every successful request (handshake) is available for Saml2.  The default is to simply call the `$next` closure.  However, let's say we want to update the user.  Here's an example of how to go about that:

```php
<?php

namespace App\Http\Middleware;

use Closure;

use RootInc\LaravelSaml2Middleware\Saml2 as Saml2;

use Auth;
use Carbon\Carbon;

use App\User;

class AppSaml2 extends Saml2
{
    protected function handlecallback($request, Closure $next, $token)
    {
        $user = Auth::user();

        $user->updated_at = Carbon::now();

        $user->save();

        return parent::handlecallback($request, $next, $token);
    }
}
```

Building off of our previous example from [Extended Installation](#extended-installation), we have a user in the Auth now (since we did `Auth::login` in the success callback).  With the user model, we can update the user's `updated_at` field.  The callback should call the closure, `$next($request);` and return it.  In our case, the default implementation does this, so we call the parent here.

#### Custom Redirect

The ability to customize the redirect method is available for Saml2.  For example, if the session token's expire, but the user is still authenticated with Laravel, we can check for that with this example:

```php
<?php

namespace App\Http\Middleware;

use RootInc\LaravelSaml2Middleware\Saml2 as Saml2;

use Auth;

class AppSaml2 extends Saml2
{
    protected function redirect($request)
    {
        if (Auth::user() !== null)
        {
            return $this->saml2($request);
        }
        else
        {
            return parent::redirect($request);
        }
    }
}
```

#### Different Login Route

The ability to change the `$login_route` in the middleware is available for Saml2.  Building off [Extended Installation](#extended-installation), in our `AppSaml2` class, we can simply set `$login_route` to whatever.  For example:

```php
<?php

namespace App\Http\Middleware;

use RootInc\LaravelSaml2Middleware\Saml2 as Saml2;

class AppSaml2 extends Saml2
{
    protected $login_route = "/";
}
```

The above would now set `$login_route` to `/` or root.

#### Getting / Overriding the Saml2 Route

The ability to get the Saml2 URL is available for Saml2.  For example, let's say we wanted to modify the Saml2 URL so that it also passed the user's email to Saml2 as a parmater.  Building off [Extended Installation](#extended-installation), in our `AppSaml2` class, we could do something like this:

```php
<?php

namespace App\Http\Middleware;

use RootInc\LaravelSaml2Middleware\Saml2 as Saml2;

use Auth;

class AppSaml2 extends Saml2
{
    //we could overload this if we wanted too.
    public function getSaml2Url($email = null)
    {
        return $this->getAuth()->login(null, [], false, false, true, true, $email);
    }

    public function saml2(Request $request)
    {
        $user = Auth::user();

        $away = $this->getSaml2Url($user ? $user->email : null);

        return redirect()->away($away);
    }
}
```

## Testing with Laravel Saml2 Middleware

We can integrate with Laravel's tests by calling `actingAs` for HTTP tests or `loginAs` with Dusk.  This assumes that we are using the `Auth::login` method in the success callback, shown at [Extended Installation](#extended-installation).  There is no need to do anything in our `AppSaml2` class, unless we needed to overwrite the default behavior, which is shown below:

```php
<?php

namespace App\Http\Middleware;

use RootInc\LaravelSaml2Middleware\Saml2 as Saml2;

use Auth;

class AppSaml2 extends Saml2
{
    //this is the default behavior
    //overwrite to meet your needs
    protected function handleTesting(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!isset($user))
        {
            return $this->redirect($request, $next);
        }

        return $this->handlecallback($request, $next, null);
    }
}
```

The above will call the class's redirect method, if it can't find a user in Laravel's auth.  Otherwise, the above will call the class's handlecallback method.  Therefore, tests can check if the correct redirection is happening, or that handlecallback is working correctly (which by default calls `$next($request);`).

## Contributing

Thank you for considering contributing to the Laravel Saml2 Middleware! To encourage active collaboration, we encourage pull requests, not just issues.

If you file an issue, the issue should contain a title and a clear description of the issue. You should also include as much relevant information as possible and a code sample that demonstrates the issue. The goal of a issue is to make it easy for yourself - and others - to replicate the bug and develop a fix.

## License

The Laravel Saml2 Middleware is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
