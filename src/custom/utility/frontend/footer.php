<?php if ((isset($RSVP_PRIVATE) && !$RSVP_PRIVATE) || (($PSW->use <= 0 && $PSW->type == "single_use") || $PSW->type == "multiple_use")) { ?>
<section id="rsvp">
    <div class="content content-little">
        <div class="title a-c">
            <?=__t('components.forms.rsvp.title')?>
        </div>
        <form class="p-4 f-start w-100 d-grid col-2 gap-4 mt-10">

            <?php if (isset($RSVP_PRIVATE)  && $RSVP_PRIVATE) { ?><input type="hidden" name="password_id" value="<?=$PSW->id?>"><?php } ?>

            <input type="hidden" name="lang" value="<?=__l()?>">

            <div class="col-2">
            <?php

                $OPTIONS = [ 1 => "1 Partecipante" ];
                
                for ($i=2; $i < 3; $i++) { $OPTIONS[$i] = "$i Partecipanti"; }

                echo select(__t('components.forms.fields.participants.label'), "participants", $OPTIONS, "", "required onchange=\"checkParticipants(this.id)\"");

            ?>
            </div>
            
            <div class="col-2 d-grid col-2 gap-4">
                <div id="responsible" class="w-100 d-grid col-2 gap-4 r-gap-1 participant">
                    <div class="col-2">
                        <span class="text-small"><span id="n-partecipant">1</span>° Participante</span>
                    </div>
                    <div class="col-1">
                        <?=text(__t('components.forms.fields.name.label'), "name[]", "", "required");?>
                    </div>
                    <div class="col-1">
                        <?=text(__t('components.forms.fields.surname.label'), "surname[]", "", "required");?>
                    </div>
                </div>
            </div>

            <!-- <div class="col-2">
                <?=checkbox(__t('components.forms.fields.events.label'), "events", $EVENTI)?>
            </div> -->

            <div class="col-2">
                <?=phone(__t('components.forms.fields.phone.label'), "phone", "", "required")?>
            </div>
            
            <div class="col-2">
                <?=email(__t('components.forms.fields.email.label'), "email", "", "required")?>
            </div>

            <div class="col-2">
                <?=textarea(__t('components.forms.fields.allergies.label'), "allergies")?>
            </div>

            <div class="col-2">
                <?=textarea(__t('components.forms.fields.request.label'), "requests")?>
            </div>

            <div class="col-2">
                <?=checkbox('', 'privacy', ["true" => ["label" => __t('components.forms.fields.privacy.label'), "attribute" => "required"]], 'checkbox', '');?>
            </div>

            <div class="col-2">
                <?=checkbox('', 'photo', ["true" => ["label" => __t('components.forms.fields.privacy_photo.label'), "attribute" => "required"]], 'checkbox', '');?>
            </div>

            <div class="col-2">
                <?=submit(__t('components.buttons.send'), "send", "btn-primary c-w", "formSubmit(this.form, '/frontend/rsvp/', showResponseRSVP)")?>
            </div>

            <div class="col-2 mt-5 a-c">
                <div class="text">
                    <?=__t('components.terms.concierge')?>: <br>
                    <a href="mailto:<?=$SOCIETY->email?>"><?=$SOCIETY->email?></a>
                </div>
            </div>
            
        </form>
    </div>
</section>

<script>

    function showResponseRSVP(response) {

        var container = document.querySelector("#loading-spinner .center");
        container.classList.add("w-80");

        if (response.success) {
            container.innerHTML = '<div class="title-big a-c"><i class="bi bi-check2-circle tx-success"></i></div><div class="subtitle mt-8 a-c"><?=__t('components.forms.rsvp.success')?></div><div class="c-w mt-10"><a onclick="location.reload(); " class="btn btn-primary c-w"><?=__t('components.buttons.back_site')?></a></div>';
        } else {
            container.innerHTML = '<div class="title-big a-c"><i class="bi bi-x-circle tx-danger"></i></div><div class="subtitle mt-8 a-c"><?=__t('components.forms.rsvp.error')?></div><div class="c-w mt-10"><a onclick="loadingSpinner();" class="btn btn-primary c-w"><?=__t('components.buttons.retry')?></a></div>';
        }

    }

    function checkParticipants(partecipant) {

        var input = document.querySelectorAll('.participant');
        var inputLenght = input.length;

        if (inputLenght < partecipant) {

            for (let i = inputLenght; i < partecipant; i++) {

                var original = document.getElementById('responsible');
                var copy = original.cloneNode(true);
                copy.id = "participant-" + i;
                original.parentNode.appendChild(copy);

                var randID = code(5);

                var labels = document.querySelectorAll("#participant-"+i+" label");

                var inputName = document.querySelector("#participant-"+i+" input[name='name[]']");
                inputName.id = randID+"-name";
                labels[0].setAttribute("for", randID+"-name");
                inputName.value = "";

                var inputSurname = document.querySelector("#participant-"+i+" input[name='surname[]']");
                inputSurname.id = randID+"-surname";
                labels[1].setAttribute("for", randID+"-surname");
                inputSurname.value = "";

                var labelPartecipant = document.querySelector("#participant-"+i+" #n-partecipant");
                i++;
                labelPartecipant.innerHTML = i;
                i--;

            }

        } else {

            inputLenght--;
            
            for (let i = inputLenght; i >= partecipant; i--) {
                const element = document.getElementById('participant-'+i);
                element.remove();
            }

        }

        setInput();
        check();

    };

    window.addEventListener('loaded', (event) => { checkParticipants(document.querySelector("select[name='participants']").value); });

</script>
<?php } else { ?>
<section id="rsvp">
    <div class="content content-little">
        <div class="title a-c">
            <?=__t('components.forms.rsvp.send')?>
        </div>
        <div class="subtitle a-c mt-4">
            <?=__t('components.terms.waiting_date')?>
        </div>
    </div>
</section>
<?php } ?>

<footer class="bg-primary tx-white">
    <div class="content">

        <div class="subtitle a-c"> <?=$EVENT->name?> </div>
        <div class="text mt-1 a-c"> <?=$EVENT->datePretty?> </div>

        <div class="text-small mt-8 a-c">
            <?=__t('components.terms.credit_by')?> <a href="https://www.wonderimage.it/" target="_blank" rel="noopener noreferrer">Wonder Image</a>
        </div>

    </div>
</footer>