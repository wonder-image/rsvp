<?php if ((isset($RSVP_PRIVATE) && !$RSVP_PRIVATE) || (($PSW->use <= 0 && $PSW->type == "single_use") || $PSW->type == "multiple_use")) { ?>
<section id="rsvp">
    <div class="content content-little">
        <div class="title a-c">
            <?=$TEXT->title->confirm_participation?>
        </div>
        <form class="p-4 f-start w-100 d-grid col-2 gap-4 mt-10">

            <?php if (isset($RSVP_PRIVATE)  && $RSVP_PRIVATE) : ?><input type="hidden" name="password_id" value="<?=$PSW->id?>"><? endif; ?>

            <input type="hidden" name="lang" value="<?=$LANG?>">

            <?=text($TEXT->form->name, "name", "", "required")?>

            <?=text($TEXT->form->surname, "surname", "", "required")?>

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

            <?=phone($TEXT->form->phone, "cel", "", "required")?>

            <?php

                $OPTIONS = [
                    1 => "1 Partecipante",
                    2 => "2 Partecipanti"
                ];

                echo select($TEXT->form->participants, "participants", $OPTIONS, "", "required");

            ?>

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