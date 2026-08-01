<?php

namespace Tests\Unit;

use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiResourceTest extends TestCase
{
    public function test_success_and_error_envelopes(): void
    {
        $ok = ApiResource::success(['id' => 1], 'done');
        $this->assertSame(200, $ok->status());
        $this->assertSame('done', $ok->getData(true)['message']);

        $error = ApiResource::error('fail', 422, ['field' => ['bad']]);
        $this->assertSame(422, $error->status());
        $this->assertSame(['field' => ['bad']], $error->getData(true)['errors']);

        $plainError = ApiResource::error('fail');
        $this->assertArrayNotHasKey('errors', $plainError->getData(true));
    }

    public function test_with_message_adds_message_to_resource(): void
    {
        $user = new User(['name' => 'Test']);
        $resource = (new ApiResource($user))->withMessage('hello');

        $payload = $resource->with(Request::create('/'));
        $this->assertSame(['message' => 'hello'], $payload);

        $without = (new ApiResource($user))->with(Request::create('/'));
        $this->assertSame([], $without);
    }
}
