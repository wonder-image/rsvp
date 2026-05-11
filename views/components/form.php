<?php
    /**
     * Componente: form RSVP completo.
     *
     * Renderizza tutto il flow di conferma partecipazione: selettore numero
     * ospiti, blocchi dinamici (nome/cognome/intolleranze) per ogni ospite,
     * contatti del referente, custom field dichiarati dall'estensione,
     * consenso privacy, bottone submit. La submission usa API_CLIENT.post
     * verso `/rsvp/` con feedback via `loadingSpinner()` del framework.
     *
     * Override consumer:
     *     custom/modules/rsvp/views/components/form.php
     *
     * Args attesi in $args (tutti opzionali, fallback su $STATE):
     *   - 'state'                array  Output di FrontendContext::state().
     *                                    Default: $STATE.
     *   - 'submit_label'         string Etichetta bottone submit. Default 'INVIA'.
     *   - 'success_text'         string Testo modal successo. Default IT.
     *   - 'error_text'           string Testo modal errore. Default IT.
     *   - 'success_button_label' string Label bottone modal successo.
     *   - 'error_button_label'   string Label bottone modal errore.
     *
     * Il componente si aspetta che API_CLIENT (head.js del framework) sia
     * disponibile a runtime e che la rotta `/api/rsvp/` sia registrata.
     */

    $args = $args ?? [];
    $state = is_array($args['state'] ?? null)
        ? $args['state']
        : (is_array($STATE ?? null) ? $STATE : []);

    $session = is_array($state['session'] ?? null) ? $state['session'] : [];
    $visibleEvents = is_array($state['visible_events'] ?? null) ? $state['visible_events'] : [];
    $requiresInviteCode = !empty($state['requires_invite_code']);
    $hasSession = (int) ($session['id'] ?? 0) > 0;
    $canAccessForm = !$requiresInviteCode || $hasSession;
    $locale = (string) ($state['locale'] ?? (function_exists('__l') ? __l() : 'it'));
    $maxParticipants = max(1, (int) ($state['max_participants'] ?? 1));
    $loginUrl = function_exists('__r') ? __r('rsvp.login') : '/rsvp/login/';
    $privacyField = function_exists('inputAcceptDocument')
        ? inputAcceptDocument('privacy_policy', 'required')
        : '';
    $customFields = is_array($state['custom_fields'] ?? null) ? $state['custom_fields'] : [];

    $submitLabel = (string) ($args['submit_label'] ?? 'INVIA');
    $successText = (string) ($args['success_text'] ?? 'La tua partecipazione è stata confermata!');
    $errorText   = (string) ($args['error_text'] ?? 'Non è stato possibile inviare la tua conferma. Riprova.');
    $successButton = (string) ($args['success_button_label'] ?? 'Torna al sito');
    $errorButton   = (string) ($args['error_button_label'] ?? 'Riprova');

    $escape = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $partecipantiOptions = [];
    for ($i = 1; $i <= $maxParticipants; $i++) {
        $partecipantiOptions[(string) $i] = (string) $i;
    }

    $renderCustomField = static function (string $key, array $def): string {
        $type = (string) ($def['type'] ?? 'text');
        $label = (string) ($def['label'] ?? $key);
        $value = $def['value'] ?? null;
        $attr = !empty($def['required']) ? 'required' : '';
        $options = is_array($def['options'] ?? null) ? $def['options'] : [];

        return match ($type) {
            'email' => email($label, $key, $value, $attr),
            'phone' => phone($label, $key, $value, $attr),
            'number' => number($label, $key, $value, $attr),
            'textarea' => textarea($label, $key, $value, $attr),
            'select' => select($label, $key, $options, $value, $attr),
            'checkbox' => checkbox($label, $key, $options),
            default => text($label, $key, $value, $attr),
        };
    };
?>

<?php if (!$canAccessForm) { ?>
    <div class="rsvp-form-reserved">
        Per compilare questo RSVP serve un codice invito.<br>
        <a href="<?=$escape($loginUrl)?>">Vai al login</a>
    </div>
<?php } else { ?>

<form id="rsvp-form" class="rsvp-form">

    <input type="hidden" name="invite_code_id" value="<?=$escape((string) ($session['id'] ?? ''))?>">
    <input type="hidden" name="locale" value="<?=$escape($locale)?>">

    <?php
        // Se c'è un solo evento (caso wedding-only), lo passiamo nascosto.
        if (count($visibleEvents) === 1) {
            $singleKey = array_key_first($visibleEvents);
    ?>
        <input type="hidden" name="event_key" value="<?=$escape((string) $singleKey)?>">
    <?php } ?>

    <?= select('Partecipanti', 'participants_count', $partecipantiOptions, '1', 'required') ?>

    <!-- Container per i blocchi ospite dinamici -->
    <div id="rsvp-participants" class="rsvp-participants"></div>

    <template id="rsvp-guest-template">
        <div class="rsvp-guest" data-participant-block data-index="__INDEX__" data-is-child="false">
            <div class="rsvp-guest-label">__TITLE__</div>
            <div class="rsvp-guest-row">
                <div><?= text('Nome', '', null, "data-field='name' data-index='__INDEX__' required") ?></div>
                <div><?= text('Cognome', '', null, "data-field='surname' data-index='__INDEX__' required") ?></div>
            </div>
            <div class="rsvp-guest-allergies">
                <?= textarea('Intolleranze o allergie', '', null, "data-field='dietary_requirements' data-index='__INDEX__'") ?>
            </div>
            <input type="hidden" data-field="is_child" data-index="__INDEX__" value="false">
        </div>
    </template>

    <?= phone('Cellulare', 'contact_phone', null, 'required') ?>
    <?= email('Email', 'contact_email', null, 'required') ?>

    <?php foreach ($customFields as $customKey => $customDef) { ?>
        <div data-rsvp-custom-field="<?=$escape($customKey)?>">
            <?= $renderCustomField($customKey, $customDef) ?>
        </div>
    <?php } ?>

    <?php if ($privacyField !== '') { ?>
        <div><?=$privacyField?></div>
    <?php } else { ?>
        <!-- Fallback: nessun documento privacy_policy in legal_documents.
             Il SubmissionNormalizer riconosce sia `privacy[]` che `privacy`. -->
        <div>
            <?= checkbox(
                '',
                'privacy',
                ['true' => ['label' => 'Acconsento al trattamento dei dati personali', 'attribute' => 'required']],
                'checkbox'
            ) ?>
        </div>
    <?php } ?>

    <div class="rsvp-form-actions">
        <button type="button" class="btn" onclick="rsvpSubmit(this.form)"><?=$escape($submitLabel)?></button>
    </div>

    <div id="rsvp-feedback" class="rsvp-feedback"></div>

</form>

<script>
(() => {
    const form = document.getElementById('rsvp-form');
    if (!form) return;

    const participantsBox = document.getElementById('rsvp-participants');
    const participantsCount = form.elements.participants_count;
    const guestTemplate = document.getElementById('rsvp-guest-template');
    const feedback = document.getElementById('rsvp-feedback');

    const SUCCESS_TEXT = <?=json_encode($successText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
    const ERROR_TEXT = <?=json_encode($errorText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
    const SUCCESS_BUTTON = <?=json_encode($successButton, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;
    const ERROR_BUTTON = <?=json_encode($errorButton, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>;

    // Gli helper framework (text/textarea/select) generano id randomici
    // condivisi nel <template>: quando cloniamo gli id si duplicano e i
    // <label for=...> puntano al primo input. Rimappiamo per blocco.
    function uniquifyIds(html, suffix) {
        const idMap = new Map();
        return html.replace(/(id|for)=(["'])(input_[a-z0-9]+|checkbox_[a-z0-9]+)\2/g,
            (_, attr, q, id) => {
                if (!idMap.has(id)) idMap.set(id, `${id}_${suffix}`);
                return `${attr}=${q}${idMap.get(id)}${q}`;
            });
    }

    function guestHTML(index) {
        const title = `Ospite ${index + 1}`;
        const raw = guestTemplate.innerHTML
            .split('__INDEX__').join(String(index))
            .replace('__TITLE__', title);
        return uniquifyIds(raw, `g${index}`);
    }

    // I label flottanti del framework (.wi-label) ricevono i listener
    // input/focusin/focusout da head.js solo al DOMContentLoaded. Gli
    // input dinamici (Ospite N) sono iniettati dopo: riattacchiamo le
    // funzioni globali del framework se presenti, fallback locale.
    function rebindWiInputs(scope) {
        scope.querySelectorAll('[data-wi-label="true"]').forEach((el) => {
            const sync = () => {
                const container = el.parentElement;
                if (!container) return;
                if ((el.value || '').toString().trim() === '') {
                    container.classList.remove('compiled');
                } else {
                    container.classList.add('compiled');
                }
            };
            sync();
            if (typeof window.labelPosition === 'function') {
                el.addEventListener('input', window.labelPosition);
            } else {
                el.addEventListener('input', sync);
            }
            if (typeof window.labelPositionTop === 'function') {
                el.addEventListener('focusin', window.labelPositionTop);
            }
            if (typeof window.check === 'function') {
                el.addEventListener('focusin', window.check);
                el.addEventListener('focusout', window.check);
            }
        });
        if (typeof window.checkLabel === 'function') window.checkLabel();
    }

    function renderGuests() {
        const n = parseInt(participantsCount ? participantsCount.value : '1', 10) || 1;
        let html = '';
        for (let i = 0; i < n; i++) html += guestHTML(i);
        participantsBox.innerHTML = html;
        rebindWiInputs(participantsBox);
    }

    function collectGuests() {
        const grouped = {};
        participantsBox.querySelectorAll('[data-index][data-field]').forEach((el) => {
            const idx = el.getAttribute('data-index');
            const fld = el.getAttribute('data-field');
            grouped[idx] = grouped[idx] || {};
            grouped[idx][fld] = el.value;
        });
        return Object.values(grouped).map((p) => ({
            name: p.name || '',
            surname: p.surname || '',
            dietary_requirements: p.dietary_requirements || '',
            is_child: p.is_child === 'true',
        }));
    }

    function collectConsents(payload) {
        // 1) Documenti legali: accept_<doc> (checkbox) + <doc>_id (hidden).
        form.querySelectorAll('input[name^="accept_"], input[name$="_id"]').forEach((el) => {
            if (el.type === 'checkbox') {
                if (el.checked) payload[el.name] = el.value || 'true';
                return;
            }
            if (el.value !== '') payload[el.name] = el.value;
        });
        // 2) Fallback senza legal_documents: privacy[] / photo_privacy[].
        ['privacy', 'photo_privacy'].forEach((key) => {
            const checked = form.querySelector(
                `input[name="${key}[]"]:checked, input[name="${key}"]:checked`
            );
            if (checked) payload[key] = checked.value || 'true';
        });
    }

    function collectCustom(payload) {
        const custom = {};
        form.querySelectorAll('[data-rsvp-custom-field]').forEach((wrap) => {
            const key = wrap.getAttribute('data-rsvp-custom-field');
            if (!key) return;
            const multi = wrap.querySelectorAll(`input[name="${key}[]"]:checked`);
            if (multi.length > 0) {
                custom[key] = Array.from(multi).map((el) => el.value);
                return;
            }
            const el = wrap.querySelector(`[name="${key}"]`);
            if (!el) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                custom[key] = el.checked ? (el.value || 'true') : '';
                return;
            }
            custom[key] = (el.value || '').toString().trim();
        });
        payload.custom_fields = custom;
    }

    function renderResultIntoSpinner(success, message) {
        const container = document.querySelector('#loading-spinner .center');
        if (!container) {
            feedback.textContent = message;
            return;
        }
        container.classList.add('w-80');
        if (success) {
            container.innerHTML = `
                <div class="title-big a-c"><i class="bi bi-check2-circle tx-success"></i></div>
                <div class="subtitle mt-8 a-c">${SUCCESS_TEXT}</div>
                <div class="c-w mt-10"><a onclick="location.reload();" class="btn btn-primary c-w">${SUCCESS_BUTTON}</a></div>
            `;
        } else {
            container.innerHTML = `
                <div class="title-big a-c"><i class="bi bi-x-circle tx-danger"></i></div>
                <div class="subtitle mt-8 a-c">${message || ERROR_TEXT}</div>
                <div class="c-w mt-10"><a onclick="location.reload();" class="btn btn-primary c-w">${ERROR_BUTTON}</a></div>
            `;
        }
    }

    window.rsvpSubmit = async function (theForm) {
        const guests = collectGuests();
        const referente = guests[0] || { name: '', surname: '' };

        const payload = {
            invite_code_id: theForm.elements.invite_code_id.value || '',
            locale: theForm.elements.locale.value || '',
            contact_name: referente.name || '',
            contact_surname: referente.surname || '',
            contact_phone: (theForm.elements.contact_phone.value || '').trim(),
            contact_email: (theForm.elements.contact_email.value || '').trim(),
            notes: '',
            source_url: window.location.href,
            participants: guests,
            events: theForm.elements.event_key ? [theForm.elements.event_key.value] : [],
        };

        if (theForm.elements.event_key) {
            payload.event_key = theForm.elements.event_key.value;
        }

        collectConsents(payload);
        collectCustom(payload);

        if (typeof loadingSpinner === 'function') loadingSpinner();

        try {
            // API_CLIENT è inizializzato in head.js come
            // new ApiClient(pathSite+"/api", API_TOKEN). Il body non-FormData
            // viene serializzato come application/json.
            await API_CLIENT.post('/rsvp/', payload);
            theForm.reset();
            renderGuests();
            renderResultIntoSpinner(true, SUCCESS_TEXT);
        } catch (err) {
            const message = (err && (err.response || err.message)) || ERROR_TEXT;
            renderResultIntoSpinner(false, message);
        }
    };

    renderGuests();
    if (participantsCount) {
        // Il widget wi-select del framework NON dispatcha l'evento `change`:
        // fa solo `selElmnt.value = ...`. All'init copia però la property
        // `.onchange` sugli onclick delle option custom. Quindi SETTIAMO la
        // property PRIMA che body-end.js inizializzi il widget; fallback su
        // addEventListener per il caso di select native.
        participantsCount.onchange = renderGuests;
        participantsCount.addEventListener('change', renderGuests);
    }
})();
</script>

<?php } // canAccessForm ?>
