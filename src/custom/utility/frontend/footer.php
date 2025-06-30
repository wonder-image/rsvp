<?php if ((isset($RSVP_PRIVATE) && !$RSVP_PRIVATE) || (($PSW->use <= 0 && $PSW->type == "single_use") || $PSW->type == "multiple_use")) { ?>
<section id="rsvp">
    <div class="content content-little">
        <div class="title a-c">
            <?=$TEXT->title->confirm_participation?>
        </div>
        <form class="p-4 f-start w-100 d-grid col-2 gap-4 mt-10">

            <?php if (isset($RSVP_PRIVATE)  && $RSVP_PRIVATE) : ?><input type="hidden" name="password_id" value="<?=$PSW->id?>"><? endif; ?>

            <input type="hidden" name="lang" value="<?=$LANG?>">

            <div class="col-2">
            <?php

                $OPTIONS = [ 1 => "1 Partecipante" ];
                
                for ($i=2; $i < 3; $i++) { $OPTIONS[$i] = "$i Partecipanti"; }

                echo select($TEXT->form->participants, "participants", $OPTIONS, "", "required onchange=\"checkParticipants(this.id)\"");

            ?>
            </div>
            
            <div class="col-2 d-grid col-2 gap-4">
                <div id="responsible" class="w-100 d-grid col-2 gap-4 r-gap-1 participant">
                    <div class="col-2">
                        <span class="text-small"><span id="n-partecipant">1</span>° Participante</span>
                    </div>
                    <div class="col-1">
                        <?=text($TEXT->form->name, "name[]", "", "required");?>
                    </div>
                    <div class="col-1">
                        <?=text($TEXT->form->surname, "surname[]", "", "required");?>
                    </div>
                </div>
            </div>

            <div class="col-2">
                <?php

                    $OPTIONS = [
                        "pool-party" => "Pool Party <span class='text-small'>09.06.23 - h 18.00</span>",
                        "wedding" => "Beach Wedding Day <span class='text-small'>10.06.23 - h 19.00</span>",
                        "brunch" => "Brunch <span class='text-small'>11.06.23 - h 11.30</span>",
                    ];
                
                    echo checkbox($TEXT->form->events, "events", $OPTIONS);
                
                ?>
            </div>

            <div class="col-2">
                <?=phone($TEXT->form->phone, "cel", "", "required")?>
            </div>
            
            <div class="col-2">
                <?=email($TEXT->form->email, "email", "", "required")?>
            </div>

            <div class="col-2">
                <?=textarea($TEXT->form->allergies, "allergies")?>
            </div>

            <div class="col-2">
                <?=textarea($TEXT->form->requests, "requests")?>
            </div>

            <div class="col-2">
                <?=checkbox('', 'privacy', ["true" => ["label" => $TEXT->form->privacy, "attribute" => "required"]], 'checkbox', '');?>
            </div>

            <div class="col-2">
                <?=submit($TEXT->form->send, "send", "btn-primary c-w", "sendRSVP(this)")?>
            </div>
            
        </form>
    </div>
</section>

<script>

    function sendRSVP(button) {

        loadingSpinner();

        var formInput = button.form.elements;

        const ARRAY = {};

        for (let i = 0; i < formInput.length; i++) {

            var add = false;

            var input = formInput[i];

            if (input.type == 'checkbox' || input.type == 'radio') {
                if (input.checked == true) { var add = true; }
            } else {
                if (input.value != "") { var add = true;}
            }

            if (add) {

                var inputName = input.name;
                var inputValue = input.value;

                if (inputName.includes("[]")) {
                    inputName = inputName.replace("[]", "");

                    if (inputName in ARRAY) {
                        inputName = inputName.replace("[]", "");
                        ARRAY[inputName].push(inputValue);
                    } else {
                        inputName = inputName.replace("[]", "");
                        ARRAY[inputName] = [];
                        ARRAY[inputName].push(inputValue);
                    }
                    
                } else {
                    if (inputName in ARRAY) {
                    } else {
                        ARRAY[inputName] = inputValue;
                    }
                }

            }

        }

        var form = JSON.stringify(ARRAY);

        $.ajax({
            type: "POST",
            url: pathSite+'/api/frontend/rsvp.php',
            data: { 
                post: 'true',
                form: form
            }, 
            success: function (data) {

                if (data != '') {

                    var container = document.querySelector("#loading-spinner .center");
                    container.classList.add("w-80");
                    container.innerHTML = '<div class="title-big a-c"><i class="bi bi-x-circle tx-danger"></i></div><div class="subtitle mt-8 a-c"><?=$TEXT->title->participation_error?></div><div class="c-w mt-10"><a onclick="location.reload();" class="btn btn-primary c-w">Riprova</a></div>';

                } else {
                    
                    var container = document.querySelector("#loading-spinner .center");
                    container.classList.add("w-80");
                    container.innerHTML = '<div class="title-big a-c"><i class="bi bi-check2-circle tx-success"></i></div><div class="subtitle mt-8 a-c"><?=$TEXT->title->participation_success?></div><div class="c-w mt-10"><a onclick="location.reload(); " class="btn btn-primary c-w">Torna al sito</a></div>';

                }

            },
            error: function (XMLHttpRequest) {
                ajaxRequestError(XMLHttpRequest);
                loadingSpinner();
            }
        });

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

                var inputName = document.querySelector("#participant-"+i+" input[name='name[]']");
                inputName.value = "";

                var inputSurname = document.querySelector("#participant-"+i+" input[name='surname[]']");
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
            <?=$TEXT->title->participation_send?>
        </div>
        <div class="subtitle a-c mt-4">
            <?=$TEXT->subtitle->participation_send?> <?=$EVENT->datePretty?>
        </div>
    </div>
</section>
<?php } ?>

<footer class="bg-primary tx-white">
    <div class="content">

        <div class="subtitle a-c">
            <?=$EVENT->name?>
        </div>
        <div class="text mt-1 a-c">
            <?=$EVENT->datePretty?>
        </div>

        <div class="text-small mt-8 a-c">
            Credit by <a href="https://www.wonderimage.it/" target="_blank" rel="noopener noreferrer">Wonder Image</a>
        </div>

    </div>
</footer>