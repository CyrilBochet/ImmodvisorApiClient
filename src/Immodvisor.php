<?php

namespace ImmodvisorApiClient\Immodvisor;

class Immodvisor
{
    /**
     * Get the last reviews for a company.
     *
     * @param string $apiKey
     * @param string $saltIn
     * @param string $saltOut
     * @param int $idCompany
     * @param int $maxReviews
     * @param string $env
     *
     * @return array
     */
    public function getLastReview(string $apiKey, string $saltIn, string $saltOut, int $idCompany, int $maxReviews, string $env = 'prod'): array
    {
        $feedbacks = [];
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $reviewsData = $api->reviewList($idCompany)->parse();
            $brand_json = $api->companyGet($idCompany)->get();
            $brand = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);

            if (isset($brand["datas"]["company"]["rating"])) {
                $feedbacks = array_slice($reviewsData->datas->reviews, 0, $maxReviews);
            }
        } catch (\Exception) {
        }

        return ['reviews' => $feedbacks];
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
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $brand_json = $api->companyGet($idCompany)->get();
            $brand = json_decode($brand_json, true, 512, JSON_THROW_ON_ERROR);

            if (isset($brand["datas"]["company"]["rating"])) {
                $infoCompany = [
                    'rate' => $brand["datas"]["company"]["rating"],
                    'city' => $brand["datas"]["company"]["address"]["city"]
                ];
            }
        } catch (\Exception) {

        }

        return $infoCompany;
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
