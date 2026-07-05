<?php

/** The Filament admin panel is gated to is_admin users. SPEC §2 / §7. */
it('serves the admin login page to guests', function () {
    $this->get('/admin/login')->assertOk();
});

it('lets a platform admin reach the dashboard', function () {
    $admin = aUser(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('forbids a non-admin user from the dashboard', function () {
    $user = aUser(['is_admin' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
