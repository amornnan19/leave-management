<?php

test('admin login page returns 200 and contains GhostShift branded label', function (): void {
    $this->get('/admin/login')
        ->assertStatus(200)
        ->assertSee('SECURE ACCESS');
});

test('portal login page returns 200 and contains GhostShift branded label', function (): void {
    $this->get('/portal/login')
        ->assertStatus(200)
        ->assertSee('SECURE ACCESS');
});

test('admin login page contains grain overlay markup', function (): void {
    $this->get('/admin/login')
        ->assertStatus(200)
        ->assertSee('ghostshift-grain', false);
});

test('portal login page contains grain overlay markup', function (): void {
    $this->get('/portal/login')
        ->assertStatus(200)
        ->assertSee('ghostshift-grain', false);
});
