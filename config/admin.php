<?php

use Whilesmart\Admin\Http\Controllers\AdminController;
use Whilesmart\Admin\Http\Requests\CreateOfferRequest;
use Whilesmart\Admin\Http\Requests\UpdateMailTemplateRequest;
use Whilesmart\Admin\Http\Resources\AdminUserResource;
use Whilesmart\Admin\Http\Resources\MailTemplateResource;
use Whilesmart\Admin\Models\MailTemplate;
use Whilesmart\Admin\Providers\EloquentAdminUserProvider;

return [
    'register_routes' => env('ADMIN_REGISTER_ROUTES', true),
    'route_prefix' => env('ADMIN_ROUTE_PREFIX', 'api/admin'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'write_middleware' => [],
    'mail_templates_table' => env('ADMIN_MAIL_TEMPLATES_TABLE', 'admin_mail_templates'),
    'owner' => ['type' => env('ADMIN_OWNER_TYPE', 'platform'), 'id' => env('ADMIN_OWNER_ID', 0)],
    'user_model' => null,
    'user_search_columns' => ['first_name', 'last_name', 'email'],
    'user_provider' => EloquentAdminUserProvider::class,
    'models' => ['mail_template' => MailTemplate::class],
    'requests' => [
        'update_mail_template' => UpdateMailTemplateRequest::class,
        'create_offer' => CreateOfferRequest::class,
    ],
    'resources' => ['user' => AdminUserResource::class, 'mail_template' => MailTemplateResource::class],
    'controller' => AdminController::class,
    // Classes implementing OfferProvider. Registering one puts its discounts
    // on the console; registering none leaves that page saying so.
    'offer_providers' => [],

    'templates' => [],
    'tokens' => ['first_name', 'last_name', 'name', 'email'],
    'registration_template' => 'welcome',
    'send_registration_email' => true,
];
