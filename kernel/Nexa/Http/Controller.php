<?php

namespace Nexa\Http;

use Nexa\View\TemplateEngine;
use Nexa\Attributes\AttributeProcessor;

class Controller
{
    protected $templateEngine;

    public function __construct()
    {
        $this->templateEngine = new TemplateEngine(
            $this->workspacePath('interface'),
            $this->storagePath('framework/views')
        );
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return AttributeProcessor::processMethod($this, $method, $args);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    protected function resourcesPath($path = '')
    {
        return dirname(__DIR__, 3) . '/resources/' . ltrim($path, '/');
    }

    protected function storagePath($path = '')
    {
        return dirname(__DIR__, 3) . '/storage/' . ltrim($path, '/');
    }

    protected function workspacePath($path = '')
    {
        return dirname(__DIR__, 3) . '/workspace/' . ltrim($path, '/');
    }

    protected function view($template, $data = [])
    {
        return $this->templateEngine->render($template, $data);
    }

    protected function json($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        return json_encode($data);
    }

    protected function redirect($url, $status = 302)
    {
        header('Location: '.$url, true, $status);
        exit;
    }

    /**
     * Return a success JSON response
     */
    protected function success($data = [], $status = 200)
    {
        return Response::json([
            'success' => true,
            'data' => $data
        ], $status);
    }

    /**
     * Return an error JSON response
     */
    protected function error($message = 'An error occurred', $status = 400, $errors = [])
    {
        return Response::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}