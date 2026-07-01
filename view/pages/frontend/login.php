<?php

use Wonder\Plugin\Rsvp\Rsvp;

/**
 * Pagina login RSVP.
 *
 * La route punta direttamente a questa pagina: lo stato arriva da
 * `Rsvp::context()` (condiviso e memoizzato tra le pagine RSVP) e la SEO
 * viene impostata qui, senza passare da un handler http dedicato.
 */

$session = Rsvp::context()['session'] ?? [];
$hasSession = (int) ($session['id'] ?? 0) > 0;
$homeUrl = __r('rsvp.home');

$SEO->title = __t('pages.rsvp.login.seo.title');
$SEO->description = __t('pages.rsvp.login.seo.description');
$SEO->url = __r('rsvp.login');
$SEO->breadcrumb = [];

$GLOBALS['PAGE_KEY'] = 'rsvp.login';

Rsvp::layout('auth', [
    'id' => 'rsvp-login-form',
    'title' => __t('pages.rsvp.login.title'),
    'subtitle' => $hasSession
        ? __t('pages.rsvp.login.session_text')
        : __t('pages.rsvp.login.text'),
    'onsubmit' => 'event.preventDefault(); submitRsvpLogin(this); return false;',
]);
?>

<?php if ($hasSession) { ?>
    <div class="w-100 mt-4">
        <a href="<?=e($homeUrl)?>" class="btn btn-primary w-100"><?=__t('pages.rsvp.login.home_button')?></a>
    </div>
<?php } else { ?>
    <div class="w-100 mt-6">
        <?=password(__t('components.forms.fields.password.label'), 'password', '', 'required')?>
    </div>
    <div class="w-100 mt-4">
        <?=submit(__t('components.buttons.login'), 'login', 'btn-primary w-100', 'submitRsvpLogin(this.form)')?>
    </div>
<?php } ?>
<div id="rsvp-login-feedback" class="w-100 mt-4 a-c tx-danger"></div>

<script>
    window.submitRsvpLogin = async function (form) {
        const password = (form.elements.password && form.elements.password.value || '').trim();

        if (password === '') {
            if (typeof alertToast === 'function') alertToast(905);
            return;
        }

        try {
            await API_CLIENT.post('/rsvp/login/', { password: password });
            window.location.href = <?=json_encode($homeUrl, JSON_UNESCAPED_SLASHES)?>;
        } catch (err) {
            const code = (err && err.status) || 905;
            if (typeof alertToast === 'function') {
                alertToast(code);
            } else if (err && err.response) {
                console.error('RSVP login error:', err.response);
            }
            const input = form.elements.password;
            if (input) {
                input.value = '';
                input.focus();
            }
        }
    };

    (() => {
        const form = document.getElementById('rsvp-login-form');
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitRsvpLogin(form);
        });
    })();
</script>

<?php \Wonder\View\View::end(); ?>
