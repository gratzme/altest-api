<?php

namespace App\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ShortUrls;
use Symfony\Component\HttpFoundation\Request;

/**
 * Exam Controller
 * @Route("/api", name="api_")
 */
class ExamController extends FOSRestController
{

    protected $shortUrls;

    public function __construct(ShortUrls $shortUrls)
    {
        $this->shortUrls = $shortUrls;
    }

    /**
     * Lists all urls
     * @Rest\Get("/list")
     * 
     * @return JsonResponse
     */
    public function list()
    {
        $urlList = $this->shortUrls->getUrlList();
        $response = new JsonResponse();
        $response->setData($urlList);
        return $response;
    }

    /**
     * converts long url to shortcode
     * @Rest\Post("/shorten")
     * 
     * @return JsonResponse
     */
    public function shortenUrl(Request $request)
    {
        $response = new JsonResponse();

        $url = $request->request->get('url', '');
        $url = json_decode($url, TRUE);
        
        if(empty($url)) {
            $data = [
                'code' => 404,
                'error' => 'No URL supplied.'
            ];
            $response->setData($data);
            return $response;
        }

        if(!$this->validateUrlFormat($url)) {
            $data = [
                'code' => 404,
                'error' => 'Invalid URL.'
            ];
            $response->setData($data);
            return $response;
        }

        if (!$this->verifyUrlExists($url)) {
            $data = [
                'code' => 404,
                'error' => 'URL does not appear to exist.'
            ];
            $response->setData($data);
            return $response;
        }

        $data['found'] = false;
        $shortUrlObj = $this->shortUrls->urlExistsInDb($url);
        if (empty($shortUrlObj)) {
            $shortCode = $this->shortUrls->createShortCode($url);
        } else {
            $shortCode = $shortUrlObj->getShortCode();
            $data['found'] = true;
        }

        $data['code'] = 200;
        $data['short_code'] = $shortCode;

        $response->setData($data);
        return $response;
    }

    /**
     * validates url
     *  
     */
    protected function validateUrlFormat($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    protected function verifyUrlExists($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404);
    }

    /**
     * gets long_url by short_code
     * @Rest\Post("/expand")
     * 
     * @return JsonResponse
     */
    public function expandShortenUrl(Request $request)
    {
        $response = new JsonResponse();
        
        // $shortCode = $request->request->get('code', '');
        // $shortCode = json_decode($shortCode, TRUE);
        $content = $request->getContent();
        $params = json_decode($content, TRUE);
        $response->setData($params);
        return $response;

        if(empty($shortCode)) {
            $data = [
                'code' => 404,
                'error' => 'No short code supplied.'
            ];
            $response->setData($data);
            return $response;
        }

        $urlObj = $this->shortUrls->getUrlObj($shortCode);
        if (empty($urlObj)) {
            $data = [
                'code' => 404,
                'error' => 'Short code does not exist.'
            ];
            $response->setData($data);
            return $response;
        }

        $data = [
            'code' => 200,
            'long_url' => $urlObj->getLongUrl()
        ];
        $response->setData($data);
        
        return $response;
    }
}