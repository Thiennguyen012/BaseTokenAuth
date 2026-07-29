<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'BE Nhựa API',
    description: 'API quản lý sản phẩm và biến thể sản phẩm'
)]
#[OA\Server(url: '/', description: 'Current server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Bearer token'
)]
#[OA\Tag(name: 'Auth')]
#[OA\Tag(name: 'Products')]
#[OA\Tag(name: 'Variant Groups')]
#[OA\Tag(name: 'Variant Options')]
#[OA\Tag(name: 'Product Variants')]
class ApiDocumentation {}
