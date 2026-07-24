<?php

namespace App\Exceptions;

use Exception;

class OrderCreationException extends Exception
{
    public function render($request)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
            ], 422);
        }

        return back()->withErrors(['order' => $this->getMessage()]);
    }
}
