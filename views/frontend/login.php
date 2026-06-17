<?php
    /**
     * Override RSVP login page per fatimagabrielewedding-com.
     *
     * Risolto da Wonder\Plugin\Rsvp\Rsvp::viewPath() se il file esiste qui.
     * Form di accesso con codice invito: input password + submit, layout
     * full-page con sfondo. Il submit usa API_CLIENT (Bearer + JSON) per
     * chiamare /api/rsvp/login/; in caso di successo redirect a /rsvp/.
     */

    $state = is_array($STATE ?? null) ? $STATE : [];
    $session = is_array($state['session'] ?? null) ? $state['session'] : [];
    $hasSession = (int) ($session['id'] ?? 0) > 0;
    $homeUrl = function_exists('localizedFrontendPath') ? localizedFrontendPath('rsvp') : '/it/rsvp/';
    
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

            <div class="w-100 mt-6">
                <?=password(__t('components.forms.fields.password.label'), 'password', '', 'required')?>
            </div>
            <div class="w-100 mt-4">
                <?=submit(__t('components.buttons.login'), 'login', 'btn-primary w-100', 'submitRsvpLogin(this.form)')?>
            </div>
            <div id="rsvp-login-feedback" class="w-100 mt-4 a-c tx-danger"></div>
        </form>
    </div>
</section>

<script>
    window.submitRsvpLogin = async function (form) {
        const password = (form.elements.password.value || '').trim();

        if (password === '') {
            // 905 = "Password errata" nelle traduzioni framework. Usiamo
            // lo stesso codice anche per "campo vuoto" per coerenza UX.
            if (typeof alertToast === 'function') alertToast(905);
            return;
        }

        try {
            // API_CLIENT serializza il payload come application/json e
            // aggiunge il Bearer token + Accept-Language. L'endpoint
            // /api/rsvp/login/ accetta sia `code` che `password`.
            await API_CLIENT.post('/rsvp/login/', { password: password });
            window.location.href = <?=json_encode($homeUrl, JSON_UNESCAPED_SLASHES)?>;
        } catch (err) {
            // ApiClient throwa l'oggetto risposta intero in caso di fail.
            // Lo `status` arriva da InviteCodeSession::login() throwato
            // con code 905 in vendor/wonder-image/rsvp. Lo passiamo a
            // alertToast() del framework che fetcha la traduzione
            // corretta via /api/frontend/alert/ (notifications.json).
            const code = (err && err.status) || 905;
            if (typeof alertToast === 'function') {
                alertToast(code);
            } else if (err && err.response) {
                console.error('RSVP login error:', err.response);
            }
            // Reset campo per permettere il retry
            const input = form.elements.password;
            if (input) {
                input.value = '';
                input.focus();
            }
        }
    };

    // Belt & suspenders: se per qualunque motivo l'attributo onsubmit
    // sul <form> non scattasse (es. browser bug, manipolazione DOM esterna),
    // il listener sotto blocca comunque la GET-submission nativa.
    (() => {
        const form = document.getElementById('rsvp-login-form');
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitRsvpLogin(form);
        });
    })();
</script>
