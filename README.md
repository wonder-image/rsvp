# CONFIGURAZIONE



## AMBIENTE


### 1° Crea account CPanel


### 2° Effettua check
 - Certificato SSL
 - SSL Status
 - Redirect HTTPS
 - Versione PHP >8.0
 - PHP Extensions
   - imagick: `true`
   - nd_mysqli: `true`
 - PHP Options
   - display_errors: `true`
   - post_max_size: `512M`
   - short_open_tag: `true`
   - upload_max_filesize: `1G`
 
 
### 3° Crea
 - Account FTP
 - Database
 - Account database
 - Aggiungi utente a database



## APP


### 1° Installa template

Crea una cartella con il `nome azienda` aprila con VSCode, successivamente vai su `nuovo terminale` e esegui il seguente comando:

```bash
  gh repo clone wonder-image/new
```
Estrapola i file dalla cartella `new` e elimina il file `README.md`. Rinomina la cartella `site_name` con il nome del dominio e compila il file `INFO.md`. 


### 2° Aggiorna APP
```bash
  composer update wonder-image/app
  composer dump-autoload
```


### 3° Personalizza file .env
Per iniziare il nuovo sito dovrai specificare: `APP_DEBUG` `APP_URL` `ASSETS_VERSION` 

Collegarti al database: `DB_HOSTNAME` `DB_USERNAME` `DB_PASSWORD` `DB_DATABASE`

Specificare credenziali accesso: `USER_NAME` `USER_SURNAME` `USER_EMAIL` `USER_USERNAME` `USER_PASSWORD`


### 4° Personalizza sftp.json
Inserisci tutte le informazioni per accedere all'hosting


### 5° Installa/Aggiorna APP
Digita sulla barra di ricerca nomedominio.it/update/


### 6° Personalizza
Digita sulla barra di ricerca nomedominio.it/backend/

Username: `USER_USERNAME`

Password: `USER_PASSWORD`
