<?php
/**
 * Helper pour interagir avec l'API Back4app
 */

require_once 'back4app-config.php';

class Back4AppHelper {
    private $apiUrl;
    private $applicationId;
    private $restApiKey;
    private $masterKey;

    public function __construct() {
        $config = require 'back4app-config.php';
        $this->apiUrl = $config['api_url'];
        $this->applicationId = $config['application_id'];
        $this->restApiKey = $config['rest_api_key'];
        $this->masterKey = $config['master_key'] ?? '';
    }

    /**
     * Effectue une requête à l'API Back4app
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $useMasterKey = false) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'X-Parse-Application-Id: ' . $this->applicationId,
            'Content-Type: application/json'
        ];

        if ($useMasterKey && !empty($this->masterKey)) {
            $headers[] = 'X-Parse-Master-Key: ' . $this->masterKey;
        } else {
            $headers[] = 'X-Parse-REST-API-Key: ' . $this->restApiKey;
        }

        error_log('Back4App request ' . $method . ' ' . $endpoint . ' headers: ' . json_encode($headers));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error
            ];
        }

        $decoded = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw' => $response
        ];
    }

    /**
     * Crée un objet dans une classe
     */
    public function create($className, $data) {
        $useMaster = !empty($this->masterKey);
        return $this->makeRequest('/classes/' . $className, 'POST', $data, $useMaster);
    }

    /**
     * Récupère des objets d'une classe
     */
    public function get($className, $where = null, $limit = null, $order = null) {
        $endpoint = '/classes/' . $className;
        $params = [];

        if ($where !== null) {
            $params['where'] = json_encode($where);
        }
        if ($limit !== null) {
            $params['limit'] = $limit;
        }
        if ($order !== null) {
            $params['order'] = $order;
        }

        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        $useMaster = !empty($this->masterKey);
        return $this->makeRequest($endpoint, 'GET', null, $useMaster);
    }

    /**
     * Met à jour un objet
     */
    public function update($className, $objectId, $data) {
        $useMaster = !empty($this->masterKey);
        return $this->makeRequest('/classes/' . $className . '/' . $objectId, 'PUT', $data, $useMaster);
    }

    /**
     * Supprime un objet
     */
    public function delete($className, $objectId) {
        $useMaster = !empty($this->masterKey);
        return $this->makeRequest('/classes/' . $className . '/' . $objectId, 'DELETE', null, $useMaster);
    }

    /**
     * Récupère un objet par son ID
     */
    public function getById($className, $objectId) {
        $useMaster = !empty($this->masterKey);
        return $this->makeRequest('/classes/' . $className . '/' . $objectId, 'GET', null, $useMaster);
    }

    /**
     * Sauvegarde ou met à jour un objet (upsert)
     */
    public function saveOrUpdate($className, $data, $uniqueField = 'objectId') {
        // Si un objectId est fourni, on met à jour
        if (isset($data['objectId'])) {
            $objectId = $data['objectId'];
            unset($data['objectId']);
            return $this->update($className, $objectId, $data);
        }

        // Sinon, on cherche s'il existe déjà
        if ($uniqueField !== 'objectId' && isset($data[$uniqueField])) {
            $where = [$uniqueField => $data[$uniqueField]];
            $existing = $this->get($className, $where, 1);
            
            if ($existing['success'] && isset($existing['data']['results']) && count($existing['data']['results']) > 0) {
                $objectId = $existing['data']['results'][0]['objectId'];
                return $this->update($className, $objectId, $data);
            }
        }

        // Sinon, on crée
        return $this->create($className, $data);
    }
}

