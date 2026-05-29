<?php

test('landing page returns 200', function () {
    $this->get('/')->assertStatus(200);
});

test('landing page contains brand wordmark', function () {
    $this->get('/')->assertSee('Leave Management');
});

test('landing page contains mono section label', function () {
    $this->get('/')->assertSee('LEAVE MANAGEMENT');
});

test('landing page links to employee portal', function () {
    $this->get('/')->assertSee('/portal', false);
});

test('landing page links to admin panel', function () {
    $this->get('/')->assertSee('/admin', false);
});
