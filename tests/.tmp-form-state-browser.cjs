const fs = require('node:fs');
const { chromium } = require('playwright');

const componentPath = process.argv[2];
const source = fs.readFileSync(componentPath, 'utf8');

const fieldMatch = source.match(/    function fieldIsValid\(input\) \{[\s\S]*?\n    \}\n\n    function syncSubmitState/);
const submitMatch = source.match(/    function syncSubmitState\(\) \{[\s\S]*?\n    \}\n\n    function toggleBlock/);

if (!fieldMatch || !submitMatch) {
    throw new Error('Impossibile estrarre le funzioni di validazione da form.php');
}

const fieldSource = fieldMatch[0].replace(/\n\n    function syncSubmitState$/, '');
const submitSource = submitMatch[0].replace(/\n\n    function toggleBlock$/, '');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();

    await page.setContent(`
        <form id="rsvp-form">
            <input type="hidden" name="invite_code_id" value="" data-wi-check="true">
            <input type="hidden" name="locale" value="it" required data-wi-check="true">

            <input type="radio" name="attendance_status" value="confirmed" checked required data-wi-check="true">
            <input type="radio" name="attendance_status" value="declined" required data-wi-check="true">

            <input type="radio" name="event_key" value="ceremony" checked required data-wi-check="true">
            <input type="radio" name="event_key" value="party" required data-wi-check="true">

            <input name="participant_name" required data-wi-check="true">
            <input name="participant_surname" required data-wi-check="true">
            <input name="contact_phone" required data-wi-check="true">
            <input name="contact_email" type="email" required data-wi-check="true">
            <input name="accept_privacy_policy" type="checkbox" required data-wi-check="true">
            <input name="inactive_required" required disabled data-wi-check="true">

            <button type="button" class="wi-submit" disabled>Invia</button>
        </form>
    `);

    await page.evaluate(({ fieldSource, submitSource }) => {
        const form = document.getElementById('rsvp-form');
        const submitButton = form.querySelector('.wi-submit');

        window.check = function check() {
            document.querySelectorAll('form .wi-submit').forEach((button) => {
                button.removeAttribute('disabled');

                Array.from(button.form.elements).forEach((input) => {
                    if (input.required) {
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            if (!input.checked) button.setAttribute('disabled', '');
                        } else if (input.value === '') {
                            button.setAttribute('disabled', '');
                        }
                    }

                    if (input.validity && !input.validity.valid) button.setAttribute('disabled', '');
                });
            });
        };

        eval(`${fieldSource}\n${submitSource}`);

        form.querySelectorAll('[data-wi-check="true"]').forEach((input) => {
            ['keyup', 'change', 'focusin', 'focusout'].forEach((eventName) => {
                input.addEventListener(eventName, window.check);
            });
        });

        ['input', 'keyup', 'change', 'focusin', 'focusout', 'click'].forEach((eventName) => {
            form.addEventListener(eventName, syncSubmitState);
        });

        window.check();
        syncSubmitState();
    }, { fieldSource, submitSource });

    const assertDisabled = async (expected, label) => {
        const actual = await page.locator('.wi-submit').isDisabled();
        if (actual !== expected) {
            throw new Error(`${label}: atteso disabled=${expected}, ottenuto ${actual}`);
        }
    };

    await assertDisabled(true, 'campi richiesti vuoti');
    await page.fill('[name="participant_name"]', 'Mario');
    await page.fill('[name="participant_surname"]', 'Rossi');
    await page.fill('[name="contact_phone"]', '+39 123456789');
    await page.fill('[name="contact_email"]', 'mario@example.com');
    await page.check('[name="accept_privacy_policy"]');
    await assertDisabled(false, 'radio group validi e form completo');

    await page.fill('[name="contact_email"]', 'email-non-valida');
    await assertDisabled(true, 'validità email nativa');
    await page.fill('[name="contact_email"]', 'mario@example.com');
    await assertDisabled(false, 'email corretta');

    await page.uncheck('[name="accept_privacy_policy"]');
    await assertDisabled(true, 'privacy non accettata');
    await page.check('[name="accept_privacy_policy"]');
    await assertDisabled(false, 'privacy accettata');

    await page.locator('[name="locale"]').evaluate((input) => {
        input.value = '';
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await assertDisabled(true, 'hidden required vuoto');

    await page.locator('[name="locale"]').evaluate((input) => {
        input.value = 'it';
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await assertDisabled(false, 'hidden required valorizzato');

    console.log('Browser form-state checks passed: 8 assertions');
    await browser.close();
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
