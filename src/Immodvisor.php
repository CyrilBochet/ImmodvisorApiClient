<?php

namespace ImmodvisorApiClient\Immodvisor;

use Exception;

class Immodvisor
{
    /**
     * Get the last reviews for a company.
     *
     * @param string $apiKey
     * @param string $saltIn
     * @param string $saltOut
     * @param int|null $idCompany
     * @param int $maxReviews
     * @param string $env
     *
     * @return array
     */
    public function getLastReview(string $apiKey, string $saltIn, string $saltOut, ?int $idCompany, int $maxReviews, string $env = 'prod'): array
    {
        $feedbacks = [];
        $fallbackUsed = $idCompany === null;
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $reviewsData = $api->reviewList($idCompany)->parse();
            $brand_json = $api->companyGet($idCompany)->get();
            $brand = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);

            $rating = $brand["datas"]["company"]["rating"] ?? 0;
            $reviews = $reviewsData->datas->reviews ?? [];

            // Fallback si aucun avis ou note nulle
            if (empty($reviews) || $rating <= 0) {
                $reviewsData = $api->reviewList(null)->parse();
                $brand_json = $api->companyGet(null)->get();
                $brand = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);
                $reviews = $reviewsData->datas->reviews ?? [];
                $fallbackUsed = true;
            }

            $feedbacks = array_slice($reviews, 0, $maxReviews);
        } catch (Exception) {
        }

        return [
            'reviews' => $feedbacks,
            'fallbackUsed' => $fallbackUsed,
            'rating' => $brand["datas"]["company"]["rating"] ?? null,
        ];
    }


    /**
     * Get company city and rating.
     *
     * @param string $apiKey
     * @param string $saltIn
     * @param string $saltOut
     * @param int $idCompany
     * @param string $env
     *
     * @return array
     */
    public function getCompanyCityAndRating(string $apiKey, string $saltIn, string $saltOut, int $idCompany, string $env = 'prod'): array
    {
        $infoCompany = [];
        $fallbackUsed = false;
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $brand_json = $api->companyGet($idCompany)->get();
            $brand = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);
            $rating = $brand["datas"]["company"]["rating"] ?? 0;

            if ($rating <= 0) {
                $brand_json = $api->companyGet(null)->get();
                $brand = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);
                $fallbackUsed = true;
            }

            if (isset($brand["datas"]["company"])) {
                $infoCompany = [
                    'rate' => $brand["datas"]["company"]["rating"],
                    'city' => $brand["datas"]["company"]["address"]["city"],
                    'fallbackUsed' => $fallbackUsed,
                ];
            }
        } catch (Exception) {
        }

        return $infoCompany;
    }


    public function getCompanyList(string $apiKey, string $saltIn, string $saltOut, int $nbReviews = 0, bool $enabledOnly = true, string $env = 'prod'): array
    {
        $companyList = [];
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $brand_json = $api->companyList($nbReviews, $enabledOnly)->get();
            $decoded = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);

            if (
                isset($decoded['status']) &&
                $decoded['status'] === 1 &&
                isset($decoded['datas']['companies']) &&
                is_array($decoded['datas']['companies'])
            ) {
                foreach ($decoded['datas']['companies'] as $company) {
                    $id = $company['id'] ?? null;
                    $name = $company['name'] ?? 'Nom inconnu';
                    if ($id) {
                        $companyList[$id] = [
                            'name' => $name,
                            'city' => $company['address']['city'] ?? null,
                            'siret' => $company['siret'] ?? null,
                            'rating' => $company['rating'] ?? null,
                        ];
                    }
                }
            }
        } catch (Exception $e) {
        }

        return $companyList;
    }


    /**
     * Initialize the API instance.
     *
     * @param string $apiKey
     * @param string $saltIn
     * @param string $saltOut
     * @param string $env
     *
     * @return Api
     */
    private function initializeApi(string $apiKey, string $saltIn, string $saltOut, string $env): Api
    {
        $api = new Api($apiKey, $saltIn, $saltOut);
        $debug = ($env !== 'prod');
        $api->env($env);
        $api->debug($debug);

        return $api;
    }
}
