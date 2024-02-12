<?php
namespace Tests\BlocksEdit\Http\fixtures;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\Route;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    /**
     * @Route("/test1", name="test1")
     */
    public function test1Action() {}

    /**
     * @Route("/test2/{id}", name="test2")
     */
    public function test2Action() {}
}
