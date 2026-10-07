<?php

namespace App\Quotations\Exceptions;

use DomainException;

class LayerRearingDisabledException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'تربية البياض غير مفعّلة في عروض الأسعار. فعّل quotations.layer_rearing_enabled قبل استخدام هذا النوع.'
        );
    }
}
