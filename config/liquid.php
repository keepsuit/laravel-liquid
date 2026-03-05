<?php

return [
    /**
     * Whether to throw exceptions when a variable is not found in the context.
     */
    'strict_variables' => false,

    /**
     * Whether to throw exceptions when a filter is not found in the environment.
     */
    'strict_filters' => false,

    /**
     * Extensions registered in the liquid environment.
     */
    'extensions' => [
        \Keepsuit\LaravelLiquid\LaravelLiquidExtension::class,
    ],
];
