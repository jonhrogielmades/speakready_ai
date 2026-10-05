<?php

namespace Tests\Feature;

use Tests\TestCase;

class RemovedSharedReviewFeatureTest extends TestCase
{
    public function test_former_public_review_and_share_endpoints_return_not_found(): void
    {
        $this->get('/shared/legacy-token')->assertNotFound();
        $this->post('/shared/legacy-token/unlock', ['password' => 'legacy-password'])->assertNotFound();
        $this->post('/shared/legacy-token/mentor-comments', ['comment' => 'Legacy comment'])->assertNotFound();
        $this->post('/session/1/share', ['enabled' => true])->assertNotFound();
    }
}
