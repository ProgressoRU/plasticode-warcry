<?php

namespace App\Controllers\Admin;

use Plasticode\Controllers\Controller;

class PlaygroundController extends Controller
{
    public function __invoke($request, $response, $args)
    {
        return $this->view->render($response, 'admin/playground/index.twig', [
            'title' => 'Playground',
        ]);
    }
}
