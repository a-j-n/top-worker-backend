<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

it('renders the scramble docs page with visible password field handling', function () {
    $this->withoutMiddleware(RestrictedDocsAccess::class)
        ->get('/docs/api')
        ->assertSuccessful()
        ->assertSee('revealDocsPasswordInputs', false)
        ->assertSee('data-password-visible', false);
});
