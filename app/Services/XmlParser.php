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

                $offers = Offer::withTrashed()->get()->mapWithKeys(function ($offer) {
                    return [
                        $offer->external_id => $offer
                    ];
                });

                $existingOffersIds = $offers->keys()->toArray();

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

                    $data['external_id'] = $externalId;

                    if (isset($offers[$externalId])) {
                        $offer = $offers[$externalId];
                        if ($offer->deleted_at) {
                            $data = array_merge(['deleted_at' => null], $data);
                        }
                        $offer->update($data);
                    } else {
                        Offer::create($data);
                    }

                    if (in_array($externalId, $existingOffersIds)) {
                        unset($existingOffersIds[array_search($externalId, $existingOffersIds)]);
                    }
                }

                Offer::whereIn('external_id', $existingOffersIds)->delete();
                Log::info('XML file has been successfully parsed and database updated.');
            });
        } catch (\Exception $e) {
            Log::error('Error parsing XML file: ' . $e->getMessage());
            throw $e;
        }
    }
}
