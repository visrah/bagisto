<?php

namespace Webkul\AbsaSpark\Providers;

use Webkul\AbsaSpark\Models\AbsaPayment;
use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        AbsaPayment::class,
    ];
}
