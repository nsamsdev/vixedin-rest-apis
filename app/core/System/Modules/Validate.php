<?php

namespace Vixedin\System\Modules;

/**
 * Class Validate
 *
 * @package Vixedin\System\Modules
 */
class Validate
{
    /**
     * @var array
     */
    private array $validationFuncs = [];

    /**
     * @param callable $callBack
     * @return void
     */
    public function addValidation(callable $callBack): void
    {
        if (is_callable($callBack)) {
            $this->validationFuncs[] = $callBack;
        }
    }

    /**
     * @return void
     */
    public function runFuncValidations(): void
    {
        foreach ($this->validationFuncs as $calls) {
            $calls();
        }
    }
}
