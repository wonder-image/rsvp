<?php // tests/RsvpApiSurfaceTest.php
declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/harness.php';

use Wonder\Plugin\Rsvp\Rsvp;

check('Rsvp::component removed', function () {
    return method_exists(Rsvp::class, 'component') === false;
});

check('Rsvp::viewPath retained (ModuleInterface)', function () {
    return method_exists(Rsvp::class, 'viewPath') === true;
});

check('viewPath falls back to module file', function () {
    $GLOBALS['ROOT'] = '/nonexistent-consumer';
    $path = Rsvp::viewPath('pages/frontend/render.php');
    return str_ends_with($path, '/view/pages/frontend/render.php')
        && !str_contains($path, '/nonexistent-consumer');
});

check('viewPath prefers consumer override at new path', function () {
    $tmp = sys_get_temp_dir().'/wi_rsvp_'.uniqid();
    @mkdir($tmp.'/custom/view/pages/rsvp/frontend', 0777, true);
    file_put_contents($tmp.'/custom/view/pages/rsvp/frontend/render.php', '<?php');
    $GLOBALS['ROOT'] = $tmp;
    return Rsvp::viewPath('pages/frontend/render.php')
        === $tmp.'/custom/view/pages/rsvp/frontend/render.php';
});

summary();
