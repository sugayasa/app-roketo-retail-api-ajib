<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthDatabaseTool implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $secretKey  =   APP_DATABASE_TOOL_SECRET_KEY;
        $userKey    =   $request->getGet('key');

        if ($userKey !== $secretKey) {
            return service('response')
                ->setStatusCode(403)
                ->setBody("Forbidden: Invalid key provided!");
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}