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
        try {
            DB::transaction(function () {
                $xml = new SimpleXMLElement(file_get_contents($this->filePath));
                $existingOffers = Offer::pluck('id', 'external_id')->toArray();

                foreach ($xml->offers->offer as $offerData) {

                    $externalId = (int)$offerData->id;
                    $arrayData = (array)$offerData;

                    $data = array_merge(array_intersect_key($arrayData, array_flip((new Offer())->getFillable())), [
                        'body_type' => $arrayData['body-type'],
                        'engine_type' => $arrayData['engine-type'],
                        'gear_type' => $arrayData['gear-type']
                    ]);

                    $data = array_map(function ($element) {
                        if ($element instanceof SimpleXMLElement) {
                            return null;
                        }
                        return $element;
                    }, $data);


                    Offer::updateOrCreate(['external_id' => $externalId], $data);

                    if (isset($existingOffers[$externalId])) {
                        unset($existingOffers[$externalId]);
                    }
                }


                Offer::whereIn('external_id', array_keys($existingOffers))->delete();
                Log::info('XML file has been successfully parsed and database updated.');
            });
        } catch (\Exception $e) {
            Log::error('Error parsing XML file: ' . $e->getMessage());
            throw $e;
        }
    }
}
