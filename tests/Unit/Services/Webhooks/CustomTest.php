<?php

namespace REBELinBLUE\Deployer\Tests\Unit\Services\Webhooks;

use Illuminate\Http\Request;
use Mockery as m;
use REBELinBLUE\Deployer\Services\Webhooks\Custom;

/**
 * @coversDefaultClass \REBELinBLUE\Deployer\Services\Webhooks\Custom
 */
class CustomTest extends WebhookTestCase
{
    /**
     * @dataProvider provideBranch
     * @covers ::handlePush
     */
    public function testHandlePushEventValid($branch)
    {
        $reason = 'Commit Log';
        $url    = 'http://www.example.com/';
        $commit = 'ee5a7ef0b320eda038d0d376a6ce50c44475efae';
        $source = 'Custom';

        $request = $this->mockRequestWithCustomPayload([
            'branch'  => $branch,
            'source'  => $source,
            'url'     => $url,
            'commit'  => $commit,
            'reason'  => $reason,
        ]);

        $custom = new Custom($request);
        $actual = $custom->handlePush();

        $this->assertInternalType('array', $actual);

        $this->assertArrayHasKey('reason', $actual);
        $this->assertArrayHasKey('branch', $actual);
        $this->assertArrayHasKey('source', $actual);
        $this->assertArrayHasKey('build_url', $actual);
        $this->assertArrayHasKey('commit', $actual);

        $this->assertSame($reason, $actual['reason']);
        $this->assertSame($branch, $actual['branch']);
        $this->assertSame($source, $actual['source']);
        $this->assertSame($url, $actual['build_url']);
        $this->assertSame($commit, $actual['commit']);
    }

    /**
     * @covers ::handlePush
     */
    public function testInvalidCommitIsCleared()
    {
        $request = $this->mockRequestWithCustomPayload([
            'branch'  => 'master',
            'source'  => 'custom',
            'commit'  => 'short',
            'url'     => '',
            'reason'  => '',
        ]);

        $custom = new Custom($request);
        $actual = $custom->handlePush();

        $this->assertEmpty($actual['commit']);
    }

    /**
     * @covers ::handlePush
     */
    public function testInvalidUrlIsCleared()
    {
        $request = $this->mockRequestWithCustomPayload([
            'branch'  => 'master',
            'source'  => 'ee5a7ef',
            'commit'  => 'short',
            'url'     => 'invalid-url',
            'reason'  => '',
        ]);

        $custom = new Custom($request);
        $actual = $custom->handlePush();

        $this->assertEmpty($actual['commit']);
    }

    /**
     * @covers ::isRequestOrigin
     */
    public function testIsRequestOriginValid()
    {
        $request = m::mock(Request::class);

        $custom = new Custom($request);
        $this->assertTrue($custom->isRequestOrigin());
    }

    private function mockRequestWithCustomPayload(array $data)
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('has')->once()->with('branch')->andReturn(true);
        $request->shouldReceive('get')->once()->with('branch')->andReturn($data['branch']);
        $request->shouldReceive('has')->once()->with('source')->andReturn(true);
        $request->shouldReceive('has')->once()->with('url')->andReturn(true);
        $request->shouldReceive('get')->once()->with('url')->andReturn($data['url']);
        $request->shouldReceive('has')->once()->with('commit')->andReturn(true);
        $request->shouldReceive('get')->once()->with('commit')->andReturn($data['commit']);
        $request->shouldReceive('get')->once()->with('reason')->andReturn($data['reason']);
        $request->shouldReceive('get')->once()->with('source')->andReturn($data['source']);

        return $request;
    }
}
