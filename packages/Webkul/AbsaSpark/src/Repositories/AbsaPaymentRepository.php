<?php

namespace Webkul\AbsaSpark\Repositories;

use Webkul\AbsaSpark\Contracts\AbsaPayment;
use Webkul\Core\Eloquent\Repository;

class AbsaPaymentRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return AbsaPayment::class;
    }
}
