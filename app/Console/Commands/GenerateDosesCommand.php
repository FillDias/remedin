<?php

namespace App\Console\Commands;

use App\Actions\GenerateDoses;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('doses:generate')]
#[Description('Create the reminders and doses of the next 48 hours from the active schedules')]
class GenerateDosesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GenerateDoses $generateDoses): int
    {
        $created = $generateDoses->forAllSchedules();

        $this->info("{$created} doses created.");

        return self::SUCCESS;
    }
}
