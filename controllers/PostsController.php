<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class PostsController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['GET'],
                    'combined' => ['GET'],
                ],
            ],
        ];
    }

    public function actionIndex(): array
    {
        $id = Yii::$app->request->get('id');
        $base = 'https://jsonplaceholder.typicode.com/posts';
        $url = $base . ($id ? '/' . urlencode((string)$id) : '');

        $response = @file_get_contents($url);

        if ($response === false) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'error' => 'Failed to fetch posts'];
        }

        $data = json_decode($response, true);

        return ['success' => true, 'data' => $data];
    }

    public function actionCombined(): array
    {
        $urls = [
            'posts' => 'https://jsonplaceholder.typicode.com/posts',
            'comments' => 'https://jsonplaceholder.typicode.com/comments',
        ];

        $multi = curl_multi_init();
        $handles = [];

        foreach ($urls as $key => $u) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $u);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }

        // execute
        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi, 1.0);
        } while ($running > 0);

        $result = [];
        $errors = [];

        foreach ($handles as $key => $ch) {
            $resp = curl_multi_getcontent($ch);
            $err = curl_error($ch);

            if ($err) {
                $errors[$key] = $err;
            } else {
                $decoded = json_decode($resp, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result[$key] = $decoded;
                } else {
                    $errors[$key] = 'invalid_json';
                }
            }

            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);

        if (!empty($errors)) {
            Yii::$app->response->statusCode = 502;

            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true, 'posts' => $result['posts'] ?? [], 'comments' => $result['comments'] ?? []];
    }
}
