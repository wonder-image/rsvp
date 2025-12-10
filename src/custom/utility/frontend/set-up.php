<?php

    // Controllo password
    if (isset($RSVP_PRIVATE) && $RSVP_PRIVATE) {
        if (isset($_SESSION['password_id']) && !empty($_SESSION['password_id'])) {

            $SQL = sqlSelect("rsvp_password", ['id' => $_SESSION['password_id']], 1);

            if ($SQL->exists) {
                if ($SQL->row['deleted'] == 'false') {
                    if ($SQL->row['active'] == 'true') {
    
                        $PSW = (object) array();
                        $PSW->id = $SQL->row['id'];
                        $PSW->type = $SQL->row['type'];
                        $PSW->use = sqlSelect("rsvp", ['password_id' => $PSW->id, 'deleted' => 'false'], 1)->Nrow;
                        $PSW->authority = sqlSelect("rsvp_authority", ['id' => $SQL->row['authority_id']], 1)->row['code'];
    
                    } else {
                        header("Location: ".__u('login'));
                    }
                } else {
                    header("Location: ".__u('login'));
                }
                
            } else {
                header("Location: ".__u('login'));
            }

        } else {
            header("Location: ".__u('login'));
        }
    }

    $SEO->title = __t("pages.home.seo.title");
    $SEO->description = __t("pages.home.seo.description");
    // $SEO->image = $PATH->upload.'/images/hero/chi-siamo-3-960.webp';