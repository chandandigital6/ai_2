<?php
return [
    // These are the legal details of the entity collecting subscriptions, NOT the seller.
    // Set real issuer values before issuing documents.
    'issuer_name' => env('GROWYA_BILLING_ISSUER_NAME', ''),
    'issuer_address' => env('GROWYA_BILLING_ISSUER_ADDRESS', ''),
    'issuer_gstin' => env('GROWYA_BILLING_ISSUER_GSTIN', ''),
];
