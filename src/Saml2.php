<?php

namespace RootInc\LaravelSaml2Middleware;

use Closure;

use OneLogin\Saml2\Auth as OneLoginAuth;
use Illuminate\Http\Request;

use Auth;

class Saml2
{
    protected $login_route = "/login";

    /**
     * Creates a loginProvider
     *
     * @return self
     */
    public function __construct()
    {
        \OneLogin\Saml2\Utils::setProxyVars($this->getConfig()['proxyVars']);
        \OneLogin\Saml2\Utils::setSelfPort( parse_url(config('app.url'), PHP_URL_SCHEME) === "https" ? 443 : 80 ); //the inner package messes up on determining if the server is hosted via 443 or not (at least on Heroku).  We override the setting here.
    }

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        $token = $request->session()->get('_rootinc_saml2_id');

        if (config('app.env') === "testing")
        {
            return $this->handleTesting($request, $next, $token);
        }

        if (!$token)
        {
            return $this->redirect($request);
        }

        $request->session()->put('_rootinc_saml2_id', $token);

        return $this->handlecallback($request, $next, $token);
    }

    /**
     * Handle an incoming request in a testing environment
     * Assumes tester is calling actingAs or loginAs during testing to run this correctly
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    protected function handleTesting(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!isset($user))
        {
            return $this->redirect($request, $next);
        }

        return $this->handlecallback($request, $next, null);
    }

    /**
     * Get the metadata for a SP
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function saml2metadata(Request $request)
    {
        $auth = $this->getAuth();
        $settings = $auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (empty($errors))
        {
            return response($metadata, 200, ['Content-Type' => 'text/xml']);
        }
        else
        {
            throw new \Exception('Invalid SP metadata: ' . implode(', ', $errors));
        }
    }

    /**
     * Gets the saml2 url
     *
     * @param string $email = null
     * @return String
     */
    public function getSaml2Url($email = null)
    {
        //originally configurable in web routes as `saml_auth`, but essentially, missing parameter was route after successful
        //these params are the defaults in the login method, except for email
        return $this->getAuth()->login(null, [], false, false, true, true, $email);
    }

    /**
     * Redirects to the Saml2 route.  Typically used to point a web route to this method.
     * For example: Route::get('/login/saml2', '\RootInc\LaravelSaml2Middleware\Saml2@saml2');
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public function saml2(Request $request)
    {
        return redirect()->away( $this->getSaml2Url() );
    }

    /**
     * Customized Redirect method
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    protected function redirect(Request $request)
    {
        return redirect($this->login_route);
    }

    /**
     * Callback after login from Saml2
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     * @throws \Exception
     */
    public function saml2callback(Request $request)
    {
        $auth = $this->getAuth();

        $auth->processResponse();
        $errors = $auth->getErrors();

        if (!$auth->isAuthenticated())
        {
            $errors[] = 'Could not authenticate';
        }

        if (!empty($errors))
        {
            $errors[] = $auth->getLastErrorReason();
            return $this->fail($request, $errors);
        }

        //store this, just in case
        $request->session()->put('_rootinc_saml2_id', $auth->getNameId());

        return $this->success($request, $auth->getNameId(), $auth->getAttributes());
    }

    /**
     * Handler that is called when a successful login has taken place for the first time
     *
     * @param \Illuminate\Http\Request $request
     * @param String $token
     * @param mixed $profile
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    protected function success(Request $request, $token, $profile)
    {
        return redirect()->intended("/");
    }

    /**
     * Handler that is called when a failed handshake has taken place
     *
     * @param \Illuminate\Http\Request $request
     * @param array $errors
     * @return string
     */
    protected function fail(Request $request, array $errors)
    {
        return implode("", $errors);
    }

    /**
     * Handler that is called every request when a user is logged in
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param String $token
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    protected function handlecallback(Request $request, Closure $next, $token)
    {
        return $next($request);
    }

    /**
     * Gets the logout url
     *
     * @return String
     */
    public function getLogoutUrl()
    {
        return $this->getAuth()->logout(null, [], null, null, true, null, null); //will actually end up in the sls endpoint
    }

    /**
     * Redirects to the Saml2 logout route.  Typically used to point a web route to this method.
     * For example: Route::get('/logout/saml2', '\RootInc\LaravelSaml2Middleware\Saml2@saml2logout');
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public function saml2logout(Request $request)
    {
        $request->session()->pull('_rootinc_saml2_id');

        return redirect()->away($this->getLogoutUrl());
    }

    /**
     * Callback after logout from Saml2
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     * @throws \Exception
     */
    public function logoutcallback(Request $request)
    {
        $auth = $this->getAuth();

        // destroy the local session by firing the Logout event
        $keep_local_session = false;
        $retrieveParametersFromServer = false; //originally configurable in saml2_settings

        $auth->processSLO($keep_local_session, null, $retrieveParametersFromServer);
        $errors = $auth->getErrors();

        if (!empty($errors))
        {
            $this->logoutfail($request, $errors);
        }

        return $this->logoutsuccess($request);
    }

    /**
     * Handler that is called when a successful logout has taken place
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    protected function logoutsuccess(Request $request)
    {
        return redirect($this->login_route)->intended("/");
    }

    /**
     * Handler that is called when a failed handshake logging out has taken place
     *
     * @param \Illuminate\Http\Request $request
     * @param array $errors
     * @return string
     */
    protected function logoutfail(Request $request, array $errors)
    {
        return implode("", $errors);
    }

    /**
     * Returns the OneLoginAuth object
     *
     * @return \OneLogin\Saml2\Auth
     */
    protected function getAuth()
    {
        $config = $this->getConfig();
        return new OneLoginAuth($config);
    }

    /**
     * The config is here vs in the Laravel's config because putenv doesn't work with config.
     *
     * @return array
     */
    protected function getConfig()
    {
        /*****
         * One Login Settings
         */

        return [
            // If 'strict' is True, then the PHP Toolkit will reject unsigned
            // or unencrypted messages if it expects them signed or encrypted
            // Also will reject the messages if not strictly follow the SAML
            // standard: Destination, NameId, Conditions ... are validated too.
            'strict' => config('saml2.strict', true),

            // Enable debug mode (to print errors)
            'debug' => config('debug', false),

            // If 'proxyVars' is True, then the Saml lib will trust proxy headers
            // e.g X-Forwarded-Proto / HTTP_X_FORWARDED_PROTO. This is useful if
            // your application is running behind a load balancer which terminates
            // SSL.
            'proxyVars' => config('saml2.proxy_vars', true), //if using Heroku, we WANT this to be true

            // Service Provider Data that we are deploying
            // NOTE - these are settings that should be set on the SP
            // We need to pass vars in for OneLoginAuth to work
            'sp' => [

                // Specifies constraints on the name identifier to be used to
                // represent the requested subject.
                // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
                'NameIDFormat' => config('saml2.sp.name_id_format', 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'),

                // Usually x509cert and privateKey of the SP are provided by files placed at
                // the certs folder. But we can also provide them with the following parameters
                'x509cert' => '',
                'privateKey' => '',

                // Identifier (URI) of the SP entity.
                // Leave blank to use the 'saml_metadata' route.
                'entityId' => config('saml2.sp.entity_id', url("/saml2/metadata")),

                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => [
                    // URL Location where the <Response> from the IdP will be returned,
                    // using HTTP-POST binding.
                    'url' => config('saml2.sp.sso', url("/login/saml2callback")),
                ],
                // Specifies info about where and how the <Logout Response> message MUST be
                // returned to the requester, in this case our SP.
                // Remove this part to not include any URL Location in the metadata.
                'singleLogoutService' => [
                    // URL Location where the <Response> from the IdP will be returned,
                    // using HTTP-Redirect binding.
                    'url' => config('saml2.sp.slo', url("/logout/saml2callback")),
                ],
            ],

            // Identity Provider Data that we want connect with our SP
            'idp' => [
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => config('saml2.idp.entity_id'),
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => [
                    // URL Target of the IdP where the SP will send the Authentication Request Message,
                    // using HTTP-Redirect binding.
                    'url' => config('saml2.idp.sso'),
                ],
                // SLO endpoint info of the IdP.
                'singleLogoutService' => [
                    // URL Location of the IdP where the SP will send the SLO Request,
                    // using HTTP-Redirect binding.
                    'url' => config('saml2.idp.slo'),
                ],
                // Public x509 certificate of the IdP
                'x509cert' => config('saml2.idp.x509'),
                /*
                 *  Instead of use the whole x509cert you can use a fingerprint
                 *  (openssl x509 -noout -fingerprint -in "idp.crt" to generate it)
                 */
                'certFingerprint' => config('saml2.idp.cert_fingerprint'),
            ],
        ];
    }
}