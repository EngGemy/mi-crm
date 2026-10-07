<?php

return [
    'map' => [
        'broiler' => 'broiler',
        'broiler_auto_exit' => 'broiler',
        'layer' => 'layers',
        'layer_auto_collect' => 'layers',
        'layer_rearing' => 'layers',
    ],

    'layer_rearing_enabled' => false,

    'spare_parts_percent' => 1,

    'payment_schedule' => [70, 25, 5],

    'layers' => require __DIR__.'/quotations/layers.php',
];
