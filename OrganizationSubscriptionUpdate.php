<?php

namespace App\Console\Commands;

use App\Models\OrganizationSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OrganizationSubscriptionUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        echo "123";
        $now = Carbon::now();
        $data = OrganizationSubscription::where('transaction_status', 2)
            ->where('status', 2)
            ->where('start_time', '<', $now)
            ->where('end_time', '<', $now)
            ->update([
                'status' => 0
            ]);
//        print_r($data);
        return 0;
    }
}
