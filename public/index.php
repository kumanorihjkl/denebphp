<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Deneb\PhpBaas\Services\ModelSyncService;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize application
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Initialize ModelSyncService
$modelSyncService = new ModelSyncService();

// Add routes
$app->get('/api/model-sync/version', function (Request $request, Response $response) {
    $data = [
        'currentVersion' => '1.0.0',
        'lastMigrationDate' => date('c')
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/model-sync/diff', function (Request $request, Response $response) use ($modelSyncService) {
    $data = json_decode($request->getBody()->getContents(), true);
    
    try {
        $changes = $modelSyncService->calculateDiff($data);
        
        $responseData = [
            'status' => 'diff',
            'currentVersion' => '0.9.0',
            'newVersion' => $data['version'] ?? '1.0.0',
            'changes' => $changes
        ];
        
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/api/model-sync/apply', function (Request $request, Response $response) use ($modelSyncService) {
    $data = json_decode($request->getBody()->getContents(), true);
    
    try {
        if (!isset($data['changes']) || !is_array($data['changes'])) {
            throw new \InvalidArgumentException('Invalid changes format');
        }

        $success = $modelSyncService->applyMigration($data['changes']);
        
        $responseData = [
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Migration applied successfully.' : 'Migration failed.',
            'currentVersion' => $data['version'] ?? '1.0.0'
        ];
        
        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

// Run application
$app->run();
