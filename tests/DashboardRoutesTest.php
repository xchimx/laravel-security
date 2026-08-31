<?php

it('protects the dashboard routes with auth middleware by default', function (string $routeName) {
    $route = app('router')->getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('web')
        ->and($route->gatherMiddleware())->toContain('auth');
})->with([
    'security.run-audit',
    'security.run-outdated',
]);
