<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class UpdatePoFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:update-po';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update an existing gettext PO file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $languages = getListOfAvailableLanguages(false);

        $locales = array_flip($languages);

        $lang = $this->choice(
            'What language?',
            array_values($languages),
        );

        $locale = $locales[$lang];

        $file = "./lang/$locale/LC_MESSAGES/messages.po";

        // Generate messages.pot
        $process1 = new Process([
            'xgettext', 
            '--files-from=./dev/list', 
            '-k_gettext', 
            '-k_ngettext:1,2', 
            '-k_pgettext:1c,2', 
            '-k_npgettext:1c,2,3',
            '--language=PHP',
            '--no-wrap',
            '-j',
            '-o',
            $file
        ]);
        $process1->run();

        if (!$process1->isSuccessful())
        {
            throw new ProcessFailedException($process1);
        }

        $process2 = new Process([
            'xgettext',
            '--files-from=./dev/list-blade',
            '-k_gettext',
            '-k_ngettext:1,2',
            '-k_pgettext:1c,2',
            '-k_npgettext:1c,2,3',
            '--language=Python',
            '--no-wrap',
            '-j',
            '--copyright-holder=Haudenschilt LLC',
            '--package-name=Family Connections',
            '--package-version='.config('fcms.version'),
            '-o',
            $file
        ]);
        $process2->run();

        if (!$process2->isSuccessful())
        {
            throw new ProcessFailedException($process2);
        }

        $this->info('File ['.$file.'] created successfully.');

        return Command::SUCCESS;
    }
}
