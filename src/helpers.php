<?php

declare(strict_types=1);

use Magdicom\Hooks;

if (! function_exists('hooks')) {
    function hooks(): Hooks
    {
        if (func_num_args() > 0) {
            throw new InvalidArgumentException(
                'hooks() no longer accepts global parameters. Pass invocation arguments to doAction(), applyFilters(), collect(), process(), or render().',
            );
        }

        return app(Hooks::class);
    }
}
