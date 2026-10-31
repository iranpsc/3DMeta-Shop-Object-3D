<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ApiDocumentationController extends Controller
{
    public function ui(): Response
    {
        return response(<<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>3D RGB API Documentation</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
  <style>body{margin:0} .swagger-ui .topbar{background:#202938}</style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>window.onload=()=>SwaggerUIBundle({url:'/docs/openapi.yaml',dom_id:'#swagger-ui',deepLinking:true,presets:[SwaggerUIBundle.presets.apis],layout:'BaseLayout'});</script>
</body>
</html>
HTML)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function specification(): Response
    {
        $path = base_path('openapi.yaml');

        abort_unless(File::exists($path), 404);

        $specification = File::get($path);
        $baseUrl = rtrim((string) config('app.url'), '/');
        $specification = str_replace('url: /api/v1', 'url: '.$baseUrl.'/api/v1', $specification);

        return response($specification, 200, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
