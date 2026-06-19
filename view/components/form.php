<?php
    /**
     * Componente: form RSVP completo.
     *
     * Input statici resi con gli helper di input frontend; i custom field
     * dell'estensione sono `FormField` che si renderizzano da soli
     * (`$input->render()`). Blocchi ospite dinamici e scelta partecipa/non
     * partecipa gestiti dal JS inline. La submission usa `formSubmit()` verso
     * la rotta resource `api.resource.rsvp-responses.store` (normalizzazione,
     * validazione e notifiche in `ResponseResource`).
     *
     * Override consumer: `custom/modules/rsvp/view/components/form.php`.
     */

    use Wonder\Plugin\Rsvp\Resources\ResponseResource;
    
    $args = $args ?? [];
    $state = is_array($args['state'] ?? null)
        ? $args['state']
        : (is_array($STATE ?? null) ? $STATE : []);

    $session = is_array($state['session'] ?? null) ? $state['session'] : [];
    $visibleEvents = is_array($state['visible_events'] ?? null) ? $state['visible_events'] : [];
    $requiresInviteCode = !empty($state['requires_invite_code']);
    $hasSession = (int) ($session['id'] ?? 0) > 0;
    $canAccessForm = !$requiresInviteCode || $hasSession;
    $locale = (string) ($state['locale'] ?? __l());
    $maxParticipants = max(1, (int) ($state['max_participants'] ?? 1));
    $enableAttendanceStatus = !empty($state['enable_attendance_status']);
    $loginUrl = __r('rsvp.login');
    $customFields = is_array($state['custom_fields'] ?? null) ? $state['custom_fields'] : [];
    $submitUrl = __e('api.resource.rsvp-responses.store');

    $partecipantiOptions = [];
    for ($i = 1; $i <= $maxParticipants; $i++) {
        $partecipantiOptions[(string) $i] = (string) $i;
    }

?>

<section class="mt-10">
    <div class="content content-medium">

        <div class="subtitle a-c tx-upper w-100"><?= __t('pages.rsvp.form.intro') ?></div>

        <?php if (!$canAccessForm) { ?>

            <div class="text a-c mt-5 tx-white">
                <?= __t('pages.rsvp.form.invite_required_text') ?><br>
            </div>
            <div class="w-100 mt-6">
                <a href="<?=e($loginUrl)?>" class="btn btn-primary c-w w-40 w-p-100">
                    <?= __t('pages.rsvp.form.invite_required_button') ?>
                </a>
            </div>

        <?php } else { ?>

            <form 
                id="rsvp-form"
                data-rsvp-form="true"
                data-success-text="<?=e(__t('pages.rsvp.form.success_text'))?>"
                data-error-text="<?=e(__t('pages.rsvp.form.error_text'))?>"
                data-success-button-label="<?=e(__t('pages.rsvp.form.success_button_label'))?>"
                data-error-button-label="<?=e(__t('pages.rsvp.form.error_button_label'))?>"
                data-participant-label="<?=e(__t('pages.rsvp.form.participant_label'))?>"
                class="p-r f-start w-100 d-grid gap-5 mt-10 col-1 bg-white tx-black p-10 b-r-25 gap-p-3 p-p-5"
                >

                <div>

                    <div class="w-15 w-p-20 c-w">
                        <?= __ri($SOCIETY->logoBlack)->size(1200)->skeleton(false)->addClass('w-100')->render() ?>
                    </div>

                    <div class="subtitle a-c tx-upper w-100 tx-primary mt-10"><?= __t('pages.rsvp.form.title') ?></div>
                    <div class="text a-c mt-2 mb-5"><?= __t('pages.rsvp.form.subtitle') ?></div>

                </div>

                <?= ResponseResource::getInput('invite_code_id')->value($session['id'] ?? '') ?>
                <?= ResponseResource::getInput('locale')->value($locale) ?>
                <?= ResponseResource::getInput('request_url')?>

                <?php if ($enableAttendanceStatus) { ?>
                    <div class="w-100">
                        <?= ResponseResource::getInput('attendance_status'); ?>
                    </div>
                <?php } ?>

                <?php 

                    /**
                     * Se ci sono più eventi visibili dai la scelta
                     */
                
                    if (count($visibleEvents) === 1) {

                        $singleKey = array_key_first($visibleEvents);
                        echo ResponseResource::getInput('event_key')->hidden()->value($singleKey);

                    } else {

                        echo ResponseResource::getInput('event_key')->options($visibleEvents);

                    }
                        
                ?>
                
                <div id="rsvp-attending-fields" class="w-100 d-grid gap-5">

                    <div class="w-100">
                        <?= ResponseResource::getInput('participants_count')->options($partecipantiOptions); ?>
                    </div>

                    <div id="rsvp-participants" class="w-100 d-grid gap-5"></div>

                    <template id="rsvp-guest-template">
                        <div class="w-100" data-rsvp-participant="__INDEX__">
                            <div class="text fw-500 w-100 mt-p-2">__TITLE__</div>
                            <div class="w-100 d-grid col-2 gap-5 gap-p-3 mt-2">
                                <?=ResponseResource::getInput('participants[__INDEX__][name]')?>
                                <?=ResponseResource::getInput('participants[__INDEX__][surname]')?>
                                <div class="col-2"><?=ResponseResource::getInput('participants[__INDEX__][dietary_requirements]')?></div>
                            </div>
                            <?=ResponseResource::getInput('participants[__INDEX__][is_child]')?>
                        </div>
                    </template>

                    <div class="w-100 bt-1 tx-black mh-p-2"></div>

                    <?php if ($customFields !== []) { ?>
                        <div class="w-100 bt-1 tx-black mh-p-2"></div>
                        <div class="w-100 d-grid gap-5">
                            <?php foreach ($customFields as $customField) { ?>
                                <div class="w-100"><?= $customField->render() ?></div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                </div>

                <div>
                    <div class="text fw-500 w-100 mb-2"><?= __t('pages.rsvp.form.contacts_label') ?></div>
                    <div id="rsvp-decline-contact" class="w-100 d-grid col-2 gap-5 gap-p-3 mt-2 mb-5" style="display:none;">
                        <?=ResponseResource::getInput('contact_name')?>
                        <?=ResponseResource::getInput('contact_surname')?>
                    </div>
                    <?=ResponseResource::getInput('contact_phone')?>
                </div>

                <?=ResponseResource::getInput('contact_email')?>

                <div class="w-100 bt-1 tx-black mh-p-2"></div>

                <?= ResponseResource::getInput('accept_privacy_policy') ?>

                <?php if (!empty($state['require_image_release'])) { ?>
                    <div id="rsvp-image-release-wrap">
                        <?= ResponseResource::getInput('accept_image_release') ?>
                    </div>
                <?php } ?>

                <div class="w-100 mt-5">
                    <button type="button" class="btn btn-primary c-w w-60 w-p-100" onclick="formSubmit(this.form, '<?=e($submitUrl)?>', rsvpFormSubmitResponse)">
                        <?= __t('pages.rsvp.form.submit_label') ?>
                    </button>
                </div>

            </form>

        <?php } ?>

    </div>
</section>

<?php if ($canAccessForm) { ?>
<script>
(() => {
    const form = document.getElementById('rsvp-form');
    if (!form) return;

    const participantsBox = document.getElementById('rsvp-participants');
    const participantsCount = form.elements.participants_count;
    const guestTemplate = document.getElementById('rsvp-guest-template');
    const attendingFields = document.getElementById('rsvp-attending-fields');
    const declineContact = document.getElementById('rsvp-decline-contact');
    const attendanceInputs = Array.from(form.querySelectorAll('input[name="attendance_status"]'));
    const imageReleaseWrap = document.getElementById('rsvp-image-release-wrap');
    const participantSelectWrap = participantsCount ? participantsCount.closest('[data-wi-select="true"]') : null;
    const attendanceWrap = attendanceInputs[0] ? attendanceInputs[0].closest('.wi-input-container') : null;

    const SUCCESS_TEXT = <?=js_e(__t('pagesvalue: .rsvp.form.success'))?>;
    const ERROR_TEXT = <?=js_e(__t('pages.rsvp.form.error_text'))?>;
    const RETRY_TEXT = <?=js_e(__t('pages.rsvp.form.error_button_label'))?>;
    const PARTICIPANT_LABEL = <?=js_e(__t('pages.rsvp.form.participant_label'))?>;

    function attendanceStatus() {
        if (attendanceInputs.length === 0) return 'confirmed';
        const selected = form.querySelector('input[name="attendance_status"]:checked');
        return selected ? selected.value : 'confirmed';
    }

    function setDisabled(scope, disabled) {
        if (!scope) return;
        scope.querySelectorAll('input, select, textarea').forEach((el) => {
            if (disabled && (el.type === 'checkbox' || el.type === 'radio')) {
                el.checked = false;
            }
            el.disabled = disabled;
        });
    }

    function toggleBlock(scope, visible) {
        if (!scope) return;
        scope.hidden = !visible;
        scope.style.display = visible ? '' : 'none';
    }

    // Gli helper framework (text/textarea/select) generano id randomici
    // condivisi nel <template>: clonandoli si duplicano e i <label for=...>
    // puntano al primo input. Rimappiamo gli id per blocco.
    function uniquifyIds(html, suffix) {
        const idMap = new Map();
        return html.replace(/(id|for)=(["'])(input_[a-z0-9]+|checkbox_[a-z0-9]+)\2/g,
            (_, attr, q, id) => {
                if (!idMap.has(id)) idMap.set(id, `${id}_${suffix}`);
                return `${attr}=${q}${idMap.get(id)}${q}`;
            });
    }

    function guestHTML(index) {
        const title = `${PARTICIPANT_LABEL} ${index + 1}`;
        const raw = guestTemplate.innerHTML
            .split('__INDEX__').join(String(index))
            .replace('__TITLE__', title);
        return uniquifyIds(raw, `g${index}`);
    }

    // setInput() della lib inizializza i data-wi-* (label flottanti,
    // validazione) sugli input iniettati a runtime.
    function bindInputs(scope) {
        if (scope && typeof setInput === 'function') setInput(scope);
    }

    function participantSnapshot() {
        return Array.from(participantsBox.querySelectorAll('[data-rsvp-participant]')).map((block, index) => ({
            index,
            name: (block.querySelector(`[name="participants[${index}][name]"]`) || {}).value || '',
            surname: (block.querySelector(`[name="participants[${index}][surname]"]`) || {}).value || '',
            dietary_requirements: (block.querySelector(`[name="participants[${index}][dietary_requirements]"]`) || {}).value || '',
            is_child: (block.querySelector(`[name="participants[${index}][is_child]"]`) || {}).value || 'false',
        }));
    }

    function restoreParticipants(values) {
        values.forEach((participant, index) => {
            [
                ['name', participant.name],
                ['surname', participant.surname],
                ['dietary_requirements', participant.dietary_requirements],
                ['is_child', participant.is_child],
            ].forEach(([field, value]) => {
                const input = participantsBox.querySelector(`[name="participants[${index}][${field}]"]`);
                if (!input) return;
                input.value = value;
            });
        });

        if (typeof checkLabel === 'function') checkLabel();
        if (typeof check === 'function') check();
    }

    function syncDeclineContactRequirements(declined) {
        if (!declineContact || typeof setRequired !== 'function') return;

        ['contact_name', 'contact_surname'].forEach((fieldName) => {
            const input = form.elements[fieldName];
            if (input) setRequired(input, declined);
        });
    }

    function renderGuests() {
        if (attendanceStatus() !== 'confirmed') {
            participantsBox.innerHTML = '';
            if (typeof check === 'function') check();
            return;
        }

        const previousParticipants = participantSnapshot();
        const n = parseInt(participantsCount ? participantsCount.value : '1', 10) || 1;
        let html = '';
        for (let i = 0; i < n; i++) html += guestHTML(i);
        participantsBox.innerHTML = html;
        bindInputs(participantsBox);
        restoreParticipants(previousParticipants);
    }

    function syncAttendanceUI() {
        const declined = attendanceStatus() === 'declined';

        toggleBlock(attendingFields, !declined);
        toggleBlock(declineContact, declined);

        setDisabled(attendingFields, declined);
        setDisabled(declineContact, !declined);
        setDisabled(imageReleaseWrap, declined);
        syncDeclineContactRequirements(declined);

        renderGuests();
        bindInputs(declineContact);
    }

    function rsvpFormErrorMessage(data) {
        if (!data || data.response == null) return ERROR_TEXT;
        if (typeof data.response === 'string') return data.response;
        if (data.response.errors && typeof data.response.errors === 'object') {
            const errors = Object.values(data.response.errors).filter(Boolean);
            if (errors.length > 0) return errors[0];
        }
        if (typeof data.response.message === 'string' && data.response.message !== '') {
            return data.response.message;
        }
        return RETRY_TEXT;
    }

    // Callback di formSubmit: mostra l'esito nel loader del framework.
    window.rsvpFormSubmitResponse = function (data) {
        const ok = !!(data && data.success);

        if (typeof loadingResponse === 'function') {
            loadingResponse({ success: ok, response: ok ? SUCCESS_TEXT : rsvpFormErrorMessage(data) });
        }

        if (ok) {
            form.reset();
            syncAttendanceUI();
        }
    };

    attendanceInputs.forEach((input) => input.addEventListener('change', syncAttendanceUI));
    if (attendanceWrap) {
        attendanceWrap.addEventListener('click', (event) => {
            if (event.target.closest('.wi-checkbox-container')) {
                requestAnimationFrame(syncAttendanceUI);
            }
        });
    }

    syncAttendanceUI();
    if (participantsCount) {
        participantsCount.addEventListener('change', renderGuests);
        participantsCount.addEventListener('input', renderGuests);
    }
    if (participantSelectWrap) {
        participantSelectWrap.addEventListener('click', (event) => {
            if (event.target.closest('.wi-input-list-value')) {
                requestAnimationFrame(renderGuests);
            }
        });
    }
})();
</script>
<?php } ?>
