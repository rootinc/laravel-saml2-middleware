<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Strict (defaults to true)
    |--------------------------------------------------------------------------
    |
    | If 'strict' is True, then the PHP Toolkit will reject unsigned
    | or unencrypted messages if it expects them signed or encrypted
    | Also will reject the messages if not strictly follow the SAML
    | standard: Destination, NameId, Conditions ... are validated too.
    |
    */
    'strict' => env('SAML2_STRICT'),

    /*
    |--------------------------------------------------------------------------
    | ProxyVars (defaults to true)
    |--------------------------------------------------------------------------
    |
    | If 'proxyVars' is True, then the Saml lib will trust proxy headers
    | e.g X-Forwarded-Proto / HTTP_X_FORWARDED_PROTO. This is useful if
    | your application is running behind a load balancer which terminates
    | SSL.
    |
    */
    'proxy_vars' => env('SAML2_PROXY_VARS'),

    /*
    |--------------------------------------------------------------------------
    | SP Vars
    |--------------------------------------------------------------------------
    |
    | Optional Variables (defaults listed)
    |
    */
    'sp' => [
        /*
        |--------------------------------------------------------------------------
        | SP NameIDFormat (defaults to "urn:oasis:names:tc:SAML:2.0:nameid-format:persistent")
        |--------------------------------------------------------------------------
        |
        | Specifies constraints on the name identifier to be used to
        | represent the requested subject.
        | Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
        |
        */
        'name_id_format' => env('SAML2_SP_NAME_ID_FORMAT'),

        /*
        |--------------------------------------------------------------------------
        | SP EntityId (defaults to url("/saml2/metadata"))
        |--------------------------------------------------------------------------
        |
        | Identifier (URI) of the SP entity.
        | Leave blank to use the 'saml_metadata' route.
        |
        */
        'entity_id' => env('SAML2_SP_ENTITY_ID'),

        /*
        |--------------------------------------------------------------------------
        | SP SSO (defaults to url("/login/saml2callback"))
        |--------------------------------------------------------------------------
        |
        | URL Location where the <Response> from the IdP will be returned,
        | using HTTP-POST binding.
        |
        */
        'sso' => env('SAML2_SP_SSO'),

        /*
        |--------------------------------------------------------------------------
        | SP SLO (defaults to url("/logout/saml2callback"))
        |--------------------------------------------------------------------------
        |
        | URL Location where the <Response> from the IdP will be returned,
        | using HTTP-Redirect binding.
        |
        */
        'slo' => env('SAML2_SP_SLO'),

        /*
        |--------------------------------------------------------------------------
        | SP x509
        |--------------------------------------------------------------------------
        |
        | Public x509 certificate of the SP
        |
        */
        'x509' => env('SAML2_SP_x509'),

        /*
        |--------------------------------------------------------------------------
        | SP Private Key
        |--------------------------------------------------------------------------
        |
        | Private key of the SP
        |
        */
        'private_key' => env('SAML2_SP_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IDP Vars
    |--------------------------------------------------------------------------
    |
    | Required Variables
    |
    */
    'idp' => [
        /*
        |--------------------------------------------------------------------------
        | IDP EntityId
        |--------------------------------------------------------------------------
        |
        | Identifier of the IdP entity  (must be a URI)
        |
        */
        'entity_id' => env('SAML2_IDP_ENTITYID'),

        /*
        |--------------------------------------------------------------------------
        | IDP SSO
        |--------------------------------------------------------------------------
        |
        | URL Target of the IdP where the SP will send the Authentication Request Message,
        | using HTTP-Redirect binding.
        |
        */
        'sso' => env('SAML2_IDP_SSO'),

        /*
        |--------------------------------------------------------------------------
        | IDP SLO
        |--------------------------------------------------------------------------
        |
        | URL Location of the IdP where the SP will send the SLO Request,
        | using HTTP-Redirect binding.
        |
        */
        'slo' => env('SAML2_IDP_SLO'),

        /*
        |--------------------------------------------------------------------------
        | IDP x509
        |--------------------------------------------------------------------------
        |
        | Public x509 certificate of the IdP
        |
        */
        'x509' => env('SAML2_IDP_x509'),

        /*
        |--------------------------------------------------------------------------
        | IDP x509
        |--------------------------------------------------------------------------
        |
        | Instead of use the whole x509cert you can use a fingerprint
        | (openssl x509 -noout -fingerprint -in "idp.crt" to generate it)
        |
        */
        'cert_fingerprint' => env('SAML2_IDP_CERT_FINGERPRINT'),
    ],
];