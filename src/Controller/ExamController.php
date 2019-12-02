<?php

namespace App\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\ShortUrls;
use Symfony\Component\HttpFoundation\Request;

/**
 * Exam Controller
 * @Route("/api", name="api_",)
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
     * @Method({"GET", "OPTIONS"})
     * 
     * @return JsonResponse
     */
    public function list()
    {
        $urlList = $this->shortUrls->getUrlList();
        $response = new JsonResponse($urlList);
        $response->headers->set('Access-Control-Allow-Origin', '*');
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
        $url = $request->request->get('url', '');
        if(empty($url)) {
            $data = [
                'code' => 404,
                'error' => 'No URL supplied.'
            ];
            $response = new JsonResponse($data);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        if(!$this->validateUrlFormat($url)) {
            $data = [
                'code' => 404,
                'error' => 'Invalid URL.'
            ];
            $response = new JsonResponse($data);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        if (!$this->verifyUrlExists($url)) {
            $data = [
                'code' => 404,
                'error' => 'URL does not appear to exist.'
            ];
            $response = new JsonResponse($data);
            $response->headers->set('Access-Control-Allow-Origin', '*');
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

        $response = new JsonResponse($data);
        $response->headers->set('Access-Control-Allow-Origin', '*');
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
     * @Rest\Post("/expand")
     */
    public function expandShortenUrl(Request $request)
    {
        $shortCode = $request->request->get('code', '');
        if(empty($shortCode)) {
            $data = [
                'code' => 404,
                'error' => 'No short code supplied.'
            ];
            $response = new JsonResponse($data);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        $urlObj = $this->shortUrls->getUrlObj($shortCode);
        if (empty($urlObj)) {
            $data = [
                'code' => 404,
                'error' => 'Short code does not exist.'
            ];
            $response = new JsonResponse($data);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        $data = [
            'code' => 200,
            'long_url' => $urlObj->getLongUrl()
        ];
        $response = new JsonResponse($data);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }
}