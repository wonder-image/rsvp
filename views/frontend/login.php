<?php
    $state = is_array($STATE ?? null) ? $STATE : [];
    $session = is_array($state['session'] ?? null) ? $state['session'] : [];
    $pageContent = is_array($state['page_content'] ?? null) ? $state['page_content'] : [];
    $loginContent = is_array($pageContent['login'] ?? null) ? $pageContent['login'] : [];
    $ambient = is_array($pageContent['ambient_background'] ?? null) ? $pageContent['ambient_background'] : [];
    $requiresInviteCode = !empty($state['requires_invite_code']);
    $homeUrl = function_exists('__r') ? __r('rsvp.home') : '/rsvp/';
    $loginApiUrl = function_exists('__r') ? __r('api.rsvp.login') : '/api/rsvp/login/';
    $logoutApiUrl = function_exists('__r') ? __r('api.rsvp.logout') : '/api/rsvp/logout/';

    $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $isTrue = static function (mixed $value): bool {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    };
    $renderVideo = static function (string $path) use ($escape): string {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        if (function_exists('__videoBG')) {
            return (string) __videoBG($path);
        }

        return '<video class="rsvp-video-fallback" autoplay muted loop playsinline>'
            .'<source src="'.$escape($path).'">'
            .'</video>';
    };
?>

<style>
    #rsvp-login-page { position: relative; min-height: 100vh; background: #000; color: #fff; overflow: hidden; }
    #rsvp-login-page .rsvp-video-fallback { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    #rsvp-login-page .rsvp-bg { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
    #rsvp-login-page .rsvp-layer { position: absolute; inset: 0; background: rgba(0, 0, 0, .6); backdrop-filter: blur(12px); }
    #rsvp-login-page .rsvp-shell { position: relative; z-index: 1; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    #rsvp-login-page .rsvp-card { width: min(720px, 100%); background: rgba(255,255,255,.93); color: #241a14; border-radius: 24px; border: 1px solid rgba(216, 179, 111, .22); box-shadow: 0 24px 80px rgba(0,0,0,.24); padding: 2rem; }
    #rsvp-login-page .rsvp-kicker { font-size: .76rem; letter-spacing: .18em; text-transform: uppercase; color: #9c6b3f; }
    #rsvp-login-page .rsvp-title { font-size: clamp(2rem, 3vw, 3rem); line-height: .95; margin-top: .75rem; }
    #rsvp-login-page .rsvp-text { margin-top: 1rem; color: #5b493d; }
    #rsvp-login-page .rsvp-form-grid { display: grid; gap: 1rem; margin-top: 2rem; }
    #rsvp-login-page .rsvp-input { width: 100%; border: 1px solid #d8c4b3; border-radius: 14px; padding: .95rem 1rem; background: #fffdfb; font: inherit; }
    #rsvp-login-page .rsvp-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
    #rsvp-login-page .rsvp-btn { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 999px; padding: .95rem 1.4rem; background: #241a14; color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
    #rsvp-login-page .rsvp-btn-light { background: transparent; color: #241a14; border: 1px solid #d8c4b3; }
    #rsvp-login-page .rsvp-note { margin-top: 1rem; color: #7c6757; font-size: .95rem; }
</style>

<div id="rsvp-login-page">
    <?php if ($isTrue($ambient['enabled'] ?? false) && trim((string) ($ambient['video'] ?? '')) !== '') { ?>
        <section class="rsvp-bg" aria-hidden="true">
            <?=$renderVideo((string) $ambient['video'])?>
            <div class="rsvp-layer"></div>
        </section>
    <?php } ?>

    <section class="rsvp-shell">
        <div class="rsvp-card">
            <div class="rsvp-kicker"><?= $escape((string) ($loginContent['kicker'] ?? 'RSVP')) ?></div>
            <h1 class="rsvp-title"><?= $escape((string) ($state['login_title'] ?? 'Accesso RSVP')) ?></h1>
            <div class="rsvp-text"><?= nl2br($escape((string) ($state['login_text'] ?? ''))) ?></div>

            <?php if (($session['id'] ?? 0) > 0) { ?>
                <div class="rsvp-note">
                    <?= $escape((string) ($loginContent['session_text'] ?? 'Sessione attiva con codice')) ?>
                    <strong><?= $escape((string) ($session['code'] ?? '')) ?></strong>.
                </div>
                <div class="rsvp-actions">
                    <a class="rsvp-btn" href="<?=$escape($homeUrl)?>"><?= $escape((string) ($loginContent['home_button'] ?? 'Vai al form RSVP')) ?></a>
                    <button type="button" class="rsvp-btn rsvp-btn-light" id="rsvp-logout"><?= $escape((string) ($loginContent['logout_button'] ?? 'Esci')) ?></button>
                </div>
            <?php } elseif ($requiresInviteCode) { ?>
                <form id="rsvp-login-form" class="rsvp-form-grid">
                    <input
                        class="rsvp-input"
                        type="text"
                        name="code"
                        placeholder="<?=$escape((string) ($loginContent['code_placeholder'] ?? 'Inserisci il codice invito'))?>"
                        required
                        autocomplete="off"
                    >
                    <button class="rsvp-btn" type="submit"><?= $escape((string) ($loginContent['login_button'] ?? 'Accedi')) ?></button>
                </form>
            <?php } else { ?>
                <div class="rsvp-note"><?= $escape((string) ($loginContent['free_text'] ?? 'Questo RSVP è libero: puoi entrare direttamente nella pagina di conferma.')) ?></div>
                <div class="rsvp-actions">
                    <a class="rsvp-btn" href="<?=$escape($homeUrl)?>"><?= $escape((string) ($loginContent['home_button'] ?? 'Vai al form RSVP')) ?></a>
                </div>
            <?php } ?>

            <div id="rsvp-login-feedback" class="rsvp-note"></div>
        </div>
    </section>
</div>

<script>
    (() => {
        const loginForm = document.getElementById('rsvp-login-form');
        const logoutButton = document.getElementById('rsvp-logout');
        const feedback = document.getElementById('rsvp-login-feedback');
        const loadingText = <?=json_encode((string) ($loginContent['loading_text'] ?? 'Accesso in corso...'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
        const errorText = <?=json_encode((string) ($loginContent['error_text'] ?? 'Accesso non riuscito.'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;

        if (loginForm) {
            loginForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                feedback.textContent = loadingText;

                const formData = new URLSearchParams();
                formData.set('code', loginForm.elements.code.value.trim());

                try {
                    const response = await fetch('<?=$escape($loginApiUrl)?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: formData.toString(),
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.response || data.message || errorText);
                    }

                    window.location.href = '<?=$escape($homeUrl)?>';
                } catch (error) {
                    feedback.textContent = error.message || errorText;
                }
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener('click', async () => {
                await fetch('<?=$escape($logoutApiUrl)?>', { method: 'POST' });
                window.location.reload();
            });
        }
    })();
</script>
