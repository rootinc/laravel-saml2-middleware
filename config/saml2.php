<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Strict
    |--------------------------------------------------------------------------
    |
    | If 'strict' is True, then the PHP Toolkit will reject unsigned
    | or unencrypted messages if it expects them signed or encrypted
    | Also will reject the messages if not strictly follow the SAML
    | standard: Destination, NameId, Conditions ... are validated too.
    |
    */
    'strict' => env('SAML2_STRICT', true),

    /*
    |--------------------------------------------------------------------------
    | IDP EntityId
    |--------------------------------------------------------------------------
    |
    | Identifier of the IdP entity  (must be a URI)
    |
    */
    'idp_entity_id' => env('SAML2_IDP_ENTITYID'),

    /*
    |--------------------------------------------------------------------------
    | IDP SSO
    |--------------------------------------------------------------------------
    |
    | URL Target of the IdP where the SP will send the Authentication Request Message,
    | using HTTP-Redirect binding.
    |
    */
    'idp_sso' => env('SAML2_IDP_SSO'),

    /*
    |--------------------------------------------------------------------------
    | IDP SLO
    |--------------------------------------------------------------------------
    |
    | URL Location of the IdP where the SP will send the SLO Request,
    | using HTTP-Redirect binding.
    |
    */
    'idp_slo' => env('SAML2_IDP_SLO'),

    /*
    |--------------------------------------------------------------------------
    | IDP x509
    |--------------------------------------------------------------------------
    |
    | Public x509 certificate of the IdP
    |
    */
    'idp_x509' => env('SAML2_IDP_x509'),

    /*
    |--------------------------------------------------------------------------
    | IDP x509
    |--------------------------------------------------------------------------
    |
    | Instead of use the whole x509cert you can use a fingerprint
    | (openssl x509 -noout -fingerprint -in "idp.crt" to generate it)
    |
    */
    'idp_cert_fingerprint' => env('SAML2_IDP_CERT_FINGERPRINT'),
];