<?php

namespace App\Console\Commands;

use App\Services\XmlParser;
use Illuminate\Console\Command;

class ParseXmlFile extends Command
{
    protected $signature = 'parse:xml {--file_path=}';
    protected $description = 'Parse XML file and update database';

    public function handle()
    {
        $filePath = $this->option('file_path');
        dd($filePath);
        $parser = new XmlParser($filePath);

        try {
            $parser->parse();
            $this->info('XML file has been successfully parsed and database updated.');
        } catch (\Exception $e) {
            $this->error('Error parsing XML file: ' . $e->getMessage());
        }
    }
}
