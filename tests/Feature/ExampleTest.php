<?php

test('home page redirects guests to SSO when LanCore is enabled', function () {
    // With lancore.enabled=true (the Feature beforeEach default), guests visiting
    // the landing page are bounced into the SSO redirect flow.
    $response = $this->get(route('home'));

    $response->assertRedirect(route('auth.redirect'));
});
