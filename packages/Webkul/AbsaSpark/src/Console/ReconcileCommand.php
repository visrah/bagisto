<?php

namespace Webkul\AbsaSpark\Console;

use Illuminate\Console\Command;
use Webkul\AbsaSpark\Services\Reconciler;

class ReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absa-spark:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-check pending Absa Spark payments and release stock held by abandoned ones';

    /**
     * Execute the console command.
     */
    public function handle(Reconciler $reconciler): int
    {
        $this->info('Checked '.$reconciler->run().' payment(s).');

        return self::SUCCESS;
    }
}
