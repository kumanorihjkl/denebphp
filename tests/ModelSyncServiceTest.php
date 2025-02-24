<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\ModelSyncService;

final class ModelSyncServiceTest extends TestCase
{
    /**
     * Test that the ModelSyncService can be instantiated.
     */
    public function testServiceInstantiation(): void
    {
        $service = new ModelSyncService();
        $this->assertInstanceOf(ModelSyncService::class, $service);
    }

    /**
     * Test the sync functionality if the method exists.
     */
    public function testSyncFunctionality(): void
    {
        $service = new ModelSyncService();
        if (method_exists($service, 'sync')) {
            $result = $service->sync();
            $this->assertNotNull($result, 'sync() should not return null');
        } else {
            $this->markTestSkipped('Method sync() is not implemented.');
        }
    }
}
