<?php \Wonder\View\View::layout('backend.show'); ?>

<?php
    use Wonder\Plugin\Rsvp\Models\Response;

    $item = is_array($ITEM ?? null) ? $ITEM : [];
    $participants = json_decode((string) ($item['participants_json'] ?? '[]'), true) ?: [];
    $events = json_decode((string) ($item['events_json'] ?? '[]'), true) ?: [];
    $consents = json_decode((string) ($item['consents_json'] ?? '[]'), true) ?: [];
    $documents = json_decode((string) ($item['legal_documents_json'] ?? '[]'), true) ?: [];
    $metadata = json_decode((string) ($item['metadata_json'] ?? '[]'), true) ?: [];
    $customFields = Response::customFieldDefinitions();
    $hasCustomFieldValues = false;
    $bookingCode = Response::resolveBookingCode($item);

    foreach ($customFields as $field) {
        if (trim((string) ($item[$field['column']] ?? '')) !== '') {
            $hasCustomFieldValues = true;
            break;
        }
    }
?>

<div class="row g-3">

    <div class="col-9">
        <div class="row g-3">

            <wi-card class="col-12">
                <div class="col-6">
                    <h6>Contatto</h6>
                    <div class="w-100 mt-2">
                        Nome: <strong><?=htmlspecialchars(trim(((string) ($item['contact_name'] ?? '')).' '.((string) ($item['contact_surname'] ?? ''))), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Email: <strong><?=htmlspecialchars((string) ($item['contact_email'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Telefono: <strong><?=htmlspecialchars((string) ($item['contact_phone'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <h6>Partecipanti</h6>
                    <div class="w-100 mt-2">
                        Totale: <strong><?=count($participants)?></strong><br>
                        Bambini: <strong><?=htmlspecialchars((string) ($item['children_count'] ?? '0'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        <?php foreach ($participants as $index => $participant) { ?>
                            Partecipante <?=($index + 1)?>:
                            <strong><?=htmlspecialchars(trim(((string) ($participant['name'] ?? '')).' '.((string) ($participant['surname'] ?? ''))), ENT_QUOTES, 'UTF-8')?></strong>
                            <?php if (!empty($participant['is_child'])) { ?> <em>(bambino)</em><?php } ?>
                            <?php if (!empty($participant['dietary_requirements'])) { ?>
                                - <?=htmlspecialchars((string) $participant['dietary_requirements'], ENT_QUOTES, 'UTF-8')?>
                            <?php } ?>
                            <br>
                        <?php } ?>
                    </div>
                </div>
            </wi-card>

            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Richieste</h6>
                    <div class="w-100 mt-2"><?=nl2br(htmlspecialchars((string) ($item['notes'] ?? '--'), ENT_QUOTES, 'UTF-8'))?></div>
                </div>
            </wi-card>

            <?php if ($hasCustomFieldValues) { ?>
            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Campi personalizzati</h6>
                    <div class="w-100 mt-2">
                        <?php foreach ($customFields as $field) { ?>
                            <?php $value = rsvpRenderCustomFieldValue($field, $item[$field['column']] ?? null); ?>
                            <?php if ($value === '') { continue; } ?>
                            <?=htmlspecialchars(rsvpCustomFieldLabel($field, (string) ($field['key'] ?? '')), ENT_QUOTES, 'UTF-8')?>:
                            <strong><?=htmlspecialchars($value, ENT_QUOTES, 'UTF-8')?></strong><br>
                        <?php } ?>
                    </div>
                </div>
            </wi-card>
            <?php } ?>

            <?php if ($metadata !== []) { ?>
            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Dati aggiuntivi</h6>
                    <div class="w-100 mt-2">
                        <pre class="mb-0"><?=htmlspecialchars(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8')?></pre>
                    </div>
                </div>
            </wi-card>
            <?php } ?>
        </div>
    </div>

    <div class="col-3">
        <div class="row g-3">
            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Dettagli</h6>
                    <div class="w-100 mt-2">
                        Codice prenotazione: <strong><?=htmlspecialchars($bookingCode !== '' ? $bookingCode : '--', ENT_QUOTES, 'UTF-8')?></strong><br>
                        Creazione: <strong><?=htmlspecialchars((string) ($item['creation'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Lingua: <strong><?=htmlspecialchars((string) ($item['locale'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Evento: <strong><?=htmlspecialchars((string) ($item['event_key'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Codice invito: <strong><?=htmlspecialchars((string) ($item['invite_code'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Gruppo invito: <strong><?=htmlspecialchars((string) ($item['invite_group_code'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Autorizzazione: <strong><?=htmlspecialchars((string) ($item['authorization_code'] ?? '--'), ENT_QUOTES, 'UTF-8')?></strong>
                    </div>
                </div>
            </wi-card>

            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Consensi</h6>
                    <div class="w-100 mt-2">
                        Privacy: <strong><?=htmlspecialchars(rsvpBooleanText($consents['privacy'] ?? false), ENT_QUOTES, 'UTF-8')?></strong><br>
                        Foto: <strong><?=htmlspecialchars(rsvpBooleanText($consents['photo'] ?? false), ENT_QUOTES, 'UTF-8')?></strong>
                    </div>
                </div>
            </wi-card>

            <?php if ($documents !== []) { ?>
            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Documenti legali</h6>
                    <div class="w-100 mt-2">
                        <?php foreach ($documents as $docType => $document) { ?>
                            <?=htmlspecialchars((string) $docType, ENT_QUOTES, 'UTF-8')?>:
                            <strong><?=htmlspecialchars(rsvpBooleanText($document['accepted'] ?? false), ENT_QUOTES, 'UTF-8')?></strong><br>
                        <?php } ?>
                    </div>
                </div>
            </wi-card>
            <?php } ?>

            <?php if ($events !== []) { ?>
            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Eventi selezionati</h6>
                    <div class="w-100 mt-2">
                        <?php foreach ($events as $event) { ?>
                            <?=htmlspecialchars((string) $event, ENT_QUOTES, 'UTF-8')?><br>
                        <?php } ?>
                    </div>
                </div>
            </wi-card>
            <?php } ?>

            <?php if (!empty($item['source_url'])) { ?>
            <wi-card class="col-12">
                <div class="col-12">
                    <h6>Origine</h6>
                    <div class="w-100 mt-2">
                        <a href="<?=htmlspecialchars((string) $item['source_url'], ENT_QUOTES, 'UTF-8')?>" target="_blank" rel="noopener noreferrer">
                            <?=htmlspecialchars((string) $item['source_url'], ENT_QUOTES, 'UTF-8')?>
                        </a>
                    </div>
                </div>
            </wi-card>
            <?php } ?>
        </div>
    </div>
</div>

<?php \Wonder\View\View::end(); ?>
