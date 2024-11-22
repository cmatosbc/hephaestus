<?php

namespace Hephaestus\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HephaestusBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
