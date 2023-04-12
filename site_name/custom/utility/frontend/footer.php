<section id="rsvp">
    <div class="content content-little">
        <div class="title a-c">
            <?=$TEXT->title->confirm_participation?>
        </div>
        <form action="" class="p-4 f-start w-100 d-grid col-2 gap-4 mt-4">
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
            <?=text($TEXT->form->phone, "cel", "", "required")?>
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
                <?=submit($TEXT->form->send, "send", "btn-primary c-w")?>
            </div>
        </form>
    </div>
</section>

<footer class="bg-primary tx-white">
    <div class="content">

        <div class="subtitle a-c">
            <?=$EVENT->name?>
        </div>
        <div class="text mt-4 a-c">
            <?=$EVENT->datePretty?>
        </div>

        <div class="text-small mt-8 a-c">
            Credit by <a href="https://www.wonderimage.it/" target="_blank" rel="noopener noreferrer">Wonder Image</a>
        </div>

    </div>
</footer>