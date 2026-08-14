<?php

namespace ImmodvisorApiClient\Immodvisor;

use Exception;
use RuntimeException;

class Immodvisor
{
    /**
     * Get the last reviews for a company.
     *
     * Retour :
     * - reviews      : liste des derniers avis (objets)
     * - fallbackUsed : true si les avis de la marque ont été utilisés à la place de ceux de la société
     * - rating       : note de la société (null si l'appel API a échoué)
     * - error        : message d'erreur si l'appel API a échoué, null sinon
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
        $rating = null;
        $error = null;
        $fallbackUsed = $idCompany === null;
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $reviewsData = $this->parseChecked($api->reviewList($idCompany));
            $brand = $this->parseChecked($api->companyGet($idCompany));

            $rating = $brand->datas->company->rating ?? null;
            $reviews = $reviewsData->datas->reviews ?? [];

            // Fallback sur la marque si aucun avis ou note nulle
            if (empty($reviews) || $rating === null || $rating <= 0) {
                $reviewsData = $this->parseChecked($api->reviewList(null));
                $brand = $this->parseChecked($api->companyGet(null));
                $rating = $brand->datas->company->rating ?? null;
                $reviews = $reviewsData->datas->reviews ?? [];
                $fallbackUsed = true;
            }

            $feedbacks = array_slice($reviews, 0, $maxReviews);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $rating = null;
            $feedbacks = [];
        }

        return [
            'reviews' => $feedbacks,
            'fallbackUsed' => $fallbackUsed,
            'rating' => $rating,
            'error' => $error,
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
            $brand = $this->parseChecked($api->companyGet($idCompany));
            $rating = $brand->datas->company->rating ?? 0;

            if ($rating <= 0) {
                $brand = $this->parseChecked($api->companyGet(null));
                $fallbackUsed = true;
            }

            if (isset($brand->datas->company)) {
                $infoCompany = [
                    'rate' => $brand->datas->company->rating ?? null,
                    'city' => $brand->datas->company->address->city ?? null,
                    'fallbackUsed' => $fallbackUsed,
                ];
            }
        } catch (Exception) {
            // Contrat historique : tableau vide si l'API est injoignable
        }

        return $infoCompany;
    }


    public function getCompanyList(string $apiKey, string $saltIn, string $saltOut, int $nbReviews = 0, bool $enabledOnly = true, string $env = 'prod'): array
    {
        $companyList = [];
        $api = $this->initializeApi($apiKey, $saltIn, $saltOut, $env);

        try {
            $decoded = $this->parseChecked($api->companyList($nbReviews, $enabledOnly));

            foreach ($decoded->datas->companies ?? [] as $company) {
                $id = $company->id ?? null;
                if ($id) {
                    $companyList[$id] = [
                        'name' => $company->name ?? 'Nom inconnu',
                        'city' => $company->address->city ?? null,
                        'siret' => $company->siret ?? null,
                        'rating' => $company->rating ?? null,
                    ];
                }
            }
        } catch (Exception) {
            // Contrat historique : tableau vide si l'API est injoignable
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


    /**
     * Vérifie la réponse du dernier service appelé (contenu, code http, status, checksum sortant)
     * puis la parse en objet.
     *
     * @throws RuntimeException si la réponse est absente ou invalide
     */
    private function parseChecked(Api $api): object
    {
        if (!$api->check()) {
            throw new RuntimeException(sprintf('Immodvisor %s : %s', $api->getService(), $api->getError() ?? 'erreur inconnue'));
        }
        $parsed = $api->parse();
        if (!is_object($parsed)) {
            throw new RuntimeException(sprintf('Immodvisor %s : réponse illisible', $api->getService()));
        }
        return $parsed;
    }
}
