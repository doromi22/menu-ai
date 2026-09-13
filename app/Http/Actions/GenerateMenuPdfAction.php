<?php

namespace App\Http\Actions;

use App\Http\Responders\GenerateMenuPdfResponder;
use Illuminate\Http\JsonResponse;

/**
 * Placeholder: menu-board PDF export is not implemented yet.
 */
class GenerateMenuPdfAction
{
    public function __construct(private GenerateMenuPdfResponder $responder) {}

    public function __invoke(): JsonResponse
    {
        return $this->responder->notYetAvailable();
    }
}
