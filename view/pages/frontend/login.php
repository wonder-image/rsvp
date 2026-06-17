<?php

use Wonder\Plugin\Rsvp\Rsvp;

$state = is_array($STATE ?? null) ? $STATE : [];
$session = is_array($state['session'] ?? null) ? $state['session'] : [];
$hasSession = (int) ($session['id'] ?? 0) > 0;
$homeUrl = __r('rsvp.home');

$SEO = $GLOBALS['SEO'] ?? (object) [];
$SEO->title = (string) ($SEO_TITLE ?? '');
$SEO->description = (string) ($SEO_DESCRIPTION ?? '');
$SEO->url = (string) ($SEO_URL ?? '');
$SEO->breadcrumb = is_array($SEO_BREADCRUMB ?? null) ? $SEO_BREADCRUMB : [];

if (trim((string) ($SEO_IMAGE ?? '')) !== '') {
    $SEO->image = (string) $SEO_IMAGE;
}

$GLOBALS['SEO'] = $SEO;
$GLOBALS['PAGE_KEY'] = (string) ($PAGE_KEY ?? 'rsvp.login');

\Wonder\View\View::layout('frontend.main');
?>

<section class="full-page">

    <?= __ri($PATH->upload.'/images/texture-1-phone.png')->fitCover()->skeleton(false)->size(1920)->render() ?>

    <div class="content content-small" style="z-index: 2;">
        <form
            id="rsvp-login-form"
            method="post"
            action=""
            autocomplete="off"
            class="w-100 p-6 center bg-secondary b-r-25"
            onsubmit="event.preventDefault(); submitRsvpLogin(this); return false;"
        >
            <img src="<?=$SOCIETY->logoWhite?>" alt="<?=e($SOCIETY->name)?>" class="w-30 c-w">

            <?php if ($hasSession) { ?>
                <div class="subtitle a-c tx-upper w-100 mt-6"><?=__t('pages.rsvp.login.title')?></div>
                <div class="text a-c w-100 mt-2"><?=__t('pages.rsvp.login.session_text')?></div>
                <div class="w-100 mt-4">
                    <a href="<?=e($homeUrl)?>" class="btn btn-primary w-100"><?=__t('pages.rsvp.login.home_button')?></a>
                </div>
            <?php } else { ?>
                <div class="subtitle a-c tx-upper w-100 mt-6"><?=__t('pages.rsvp.login.title')?></div>
                <div class="text a-c w-100 mt-2"><?=__t('pages.rsvp.login.text')?></div>
                <div class="w-100 mt-6">
                    <?=password(__t('components.forms.fields.password.label'), 'password', '', 'required')?>
                </div>
                <div class="w-100 mt-4">
                    <?=submit(__t('components.buttons.login'), 'login', 'btn-primary w-100', 'submitRsvpLogin(this.form)')?>
                </div>
            <?php } ?>
            <div id="rsvp-login-feedback" class="w-100 mt-4 a-c tx-danger"></div>
        </form>
    </div>
</section>

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
