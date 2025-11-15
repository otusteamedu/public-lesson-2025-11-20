<?php

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;

trait ApiRequestCheckTrait
{
    private function isApiRequest(Request $request):bool
    {
        return $request->attributes->get(RequestAttributesEnum::IS_API_REQUEST->value, false);
    }
}