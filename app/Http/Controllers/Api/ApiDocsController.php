<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ApiDocsController extends Controller
{
    /**
     * Serve the Swagger UI interface
     */
    public function index()
    {
        return view('api-docs.index');
    }

    /**
     * Serve the OpenAPI YAML specification
     */
    public function specification()
    {
        $swaggerFile = base_path('api-docs.yml');

        if (!File::exists($swaggerFile)) {
            return response()->json([
                'error' => 'API documentation not found',
                'message' => 'The api-docs.yml file is not available'
            ], 404);
        }

        $content = File::get($swaggerFile);

        return response($content, 200, [
            'Content-Type' => 'application/yaml',
            'Content-Disposition' => 'inline; filename="api-docs.yml"'
        ]);
    }

    /**
     * Serve the Postman collection
     */
    public function postman()
    {
        $postmanFile = base_path('postman-collection.json');

        if (!File::exists($postmanFile)) {
            return response()->json([
                'error' => 'Postman collection not found',
                'message' => 'The postman-collection.json file is not available'
            ], 404);
        }

        $content = File::get($postmanFile);

        return response($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="postman-collection.json"'
        ]);
    }

    /**
     * Show API documentation info
     */
    public function info()
    {
        return response()->json([
            'title' => 'Sweat93 Multi-Store Gym API',
            'version' => '1.0.0',
            'description' => 'Complete API documentation for the Sweat93 Gym Management System',
            'features' => [
                'Multi-store cash register system',
                'Automated income posting per completed training',
                'Store-specific AND total cash register entries',
                'Store-specific AND total business expenses',
                'Store-specific AND total bookings',
                'Custom store colors for UI differentiation',
                'Complete store management (CRUD operations)',
                'Package lifecycle management',
                'Role-based access control',
                'Complete booking management',
                'Financial reporting per store'
            ],
            'endpoints' => [
                'swagger_ui' => '/api-docs',
                'swagger_yaml' => '/api-docs/spec',
                'postman' => '/api-docs/postman',
                'info' => '/api-docs/info'
            ],
            'documentation_files' => [
                'swagger_yaml' => 'api-docs.yml',
                'postman_collection' => 'postman-collection.json',
                'readme' => 'API_DOCUMENTATION_README.md'
            ]
        ]);
    }
}
