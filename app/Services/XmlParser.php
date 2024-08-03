<?php

namespace App\Services;

use App\Models\Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class XmlParser
{
    private $filePath;

    public function __construct(string $filePath = null)
    {
        $this->filePath = $filePath ?: config('app.default_xml_path');
    }

    public function parse()
    {
        DB::beginTransaction();

        try {
            $xml = new SimpleXMLElement(file_get_contents($this->filePath));
            $existingOffers = Offer::pluck('id', 'external_id')->toArray();

            foreach ($xml->offers->offer as $offerData) {
                $externalId = (int)$offerData->id;

                $offer = Offer::updateOrCreate(                 ['external_id' => $externalId],
                    [
                        'mark' => (string)$offerData->mark,
                        'model' => (string)$offerData->model,
                        'generation' => (string)$offerData->generation ?: null,
                        'year' => (int)$offerData->year,
                        'run' => (int)$offerData->run,
                        'color' => (string)$offerData->color ?: null,
                        'body_type' => (string)$offerData->{'body-type'},
                        'engine_type' => (string)$offerData->{'engine-type'},
                        'transmission' => (string)$offerData->transmission,
                        'gear_type' => (string)$offerData->{'gear-type'},
                        'generation_id' => (string)$offerData->generation_id ?: null,
                    ]
                );

                if (isset($existingOffers[$externalId])) {
                    unset($existingOffers[$externalId]);
                }
            }


            Offer::whereIn('external_id', array_keys($existingOffers))->delete();

            DB::commit();
            Log::info('XML file has been successfully parsed and database updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error parsing XML file: ' . $e->getMessage());
            throw $e;
        }
    }
}
