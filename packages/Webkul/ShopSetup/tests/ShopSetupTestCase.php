<?php

namespace Webkul\ShopSetup\Tests;

use Webkul\Admin\Tests\AdminTestCase;

class ShopSetupTestCase extends AdminTestCase
{
    /**
     * The suite boots in full admin mode so the stock tests see every page; these tests run as a shop owner does.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['system_config_hidden' => config('shop_setup.hidden_configuration')]);
    }
}
