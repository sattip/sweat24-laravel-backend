<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweat93 Multi-Store Gym API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.11.0/favicon-32x32.png" sizes="32x32" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }

        .swagger-ui .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .swagger-ui .topbar .link {
            display: none;
        }

        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }

        .swagger-ui .info .title {
            color: #3b4151;
        }

        .custom-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 0;
        }

        .custom-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }

        .custom-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }

        .api-info {
            background: white;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .api-info h3 {
            color: #3b4151;
            margin-top: 0;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .feature-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }

        .endpoints-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .endpoints-list h4 {
            margin-top: 0;
            color: #3b4151;
        }

        .endpoint-item {
            background: white;
            margin: 5px 0;
            padding: 8px 12px;
            border-radius: 4px;
            border-left: 3px solid #28a745;
        }

        .code-snippet {
            background: #2d3748;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>🏋️ Sweat93 Gym API</h1>
        <p>Multi-Store Cash Register & Management System</p>
    </div>

    <div class="api-info">
        <h3>🚀 API Overview</h3>
        <p>Complete REST API for managing gym operations across multiple stores with automated cash register functionality.</p>

        <h4>✨ Key Features</h4>
        <div class="feature-list">
            <div class="feature-item">🏪 <strong>Multi-Store Support</strong> - Manage Βάρη & Λαγονήσι locations</div>
            <div class="feature-item">🏪 <strong>Flexible Store Views</strong> - See data per store OR across all stores</div>
            <div class="feature-item">🎨 <strong>Store Colors</strong> - Custom colors for UI differentiation (#10B981, #F59E0B)</div>
            <div class="feature-item">🏪 <strong>Store Management</strong> - Complete CRUD operations for stores</div>
            <div class="feature-item">📅 <strong>Store Bookings</strong> - Total & store-specific booking management</div>
            <div class="feature-item">💰 <strong>Full Cash Register</strong> - Complete cash management with totals & store breakdown</div>
            <div class="feature-item">💸 <strong>Auto Income Posting</strong> - Income posted automatically on booking completion</div>
            <div class="feature-item">📦 <strong>Package Management</strong> - Track usage, expiration, and renewals</div>
            <div class="feature-item">🔐 <strong>Role-Based Access</strong> - Admin, Trainer, Member permissions</div>
            <div class="feature-item">📊 <strong>Financial Reports</strong> - Per-store income/expense tracking</div>
            <div class="feature-item">🎯 <strong>Rounding Logic</strong> - Smart €100 ÷ 3 = €33.33, €33.33, €33.34</div>
            <div class="feature-item">📈 <strong>Business Expenses</strong> - Track and manage expenses per store</div>
        </div>

        <h4>📋 Quick Access</h4>
        <div class="endpoints-list">
            <h4>🔗 API Endpoints</h4>
            <div class="endpoint-item">
                <strong>GET</strong> <code>/api-docs</code> - This documentation (YAML)
            </div>
            <div class="endpoint-item">
                <strong>GET</strong> <code>/api-docs/postman</code> - Postman collection download
            </div>
            <div class="endpoint-item">
                <strong>GET</strong> <code>/api-docs/info</code> - API information (JSON)
            </div>
        </div>

        <h4>🔧 Authentication</h4>
        <p>All protected endpoints require Bearer token authentication:</p>
        <div class="code-snippet">
Authorization: Bearer your_token_here
        </div>

        <h4>📚 Base URL</h4>
        <div class="code-snippet">
Production: https://api.sweat93.gr
Development: http://localhost:8000
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '/api-docs/spec',  // Points to our API docs YAML endpoint
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                tryItOutEnabled: true,
                requestInterceptor: function(request) {
                    // Add auth token if available
                    const token = localStorage.getItem('api_token');
                    if (token) {
                        request.headers.Authorization = 'Bearer ' + token;
                    }
                    return request;
                },
                responseInterceptor: function(response) {
                    // Store auth token from login response
                    if (response.url.includes('/auth/login') && response.status === 200) {
                        try {
                            const data = JSON.parse(response.body);
                            if (data.token) {
                                localStorage.setItem('api_token', data.token);
                            }
                        } catch (e) {
                            console.log('Could not parse login response');
                        }
                    }
                    return response;
                }
            });
        };
    </script>
</body>
</html>
