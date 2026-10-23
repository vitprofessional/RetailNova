<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First-run Setup Token
    |--------------------------------------------------------------------------
    |
    | Optional shared secret for the public first administrator setup form.
    | When set, the setup request must include a matching `setup_token` field.
    | Production installations refuse first-run setup until a token is set.
    |
    */

    'token' => env('SETUP_TOKEN'),

];
