# Hook e SEO

Oltre ai [custom field](custom-field.md), l'estensione RSVP espone hook per
intervenire sul ciclo di invio e sui meta SEO delle pagine. Tutti hanno
un'implementazione vuota di default in `AbstractRsvpExtension`: override solo ciò
che ti serve.

## `beforeSubmit(array $payload): array`

Mutator del payload **prima** della persistenza. Ritorna l'array modificato (o
invariato). Lancia un'eccezione per abortire l'invio.

```php
public function beforeSubmit(array $payload): array
{
    $payload['notes'] = trim((string) ($payload['notes'] ?? ''));

    return $payload;
}
```

{% hint style="info" %}
Le colonne `meta_*` dei custom field sono risolte indipendentemente da
`beforeSubmit()`: l'hook **non** deve ricostruirle manualmente.
{% endhint %}

## `afterSubmit(array $response, array $payload): void`

Eseguito **dopo** la persistenza della risposta. Utile per side-effect: notifiche
aggiuntive, integrazioni esterne, sincronizzazioni.

```php
public function afterSubmit(array $response, array $payload): void
{
    // es. push verso un CRM esterno
    MyCrm::sync($response);
}
```

## `seo(string $page, array $state): array`

Override dei meta SEO delle pagine RSVP. `$page` vale `'home'` o `'login'`.
Ritorna un array con eventuali chiavi `title`, `description`, `image` (path o URL
assoluta). Le chiavi assenti ricadono sui default del modulo.

```php
public function seo(string $page, array $state): array
{
    if ($page === 'home') {
        return [
            'title'       => 'Conferma la tua presenza | Rossi Wedding',
            'description' => 'Compila il form per confermare la partecipazione.',
            'image'       => '/custom/assets/img/og-wedding.jpg',
        ];
    }

    return [];
}
```

Lo stesso hook SEO è applicato anche alle pagine create con
[`Rsvp::renderPage()`](../frontend/personalizzare-le-view.md#pagine-rsvp-aggiuntive),
usando come `$page` la chiave logica della pagina (es. `wishlist`).

## Riepilogo dei punti di estensione

| Metodo | Quando | Scopo |
| --- | --- | --- |
| `formInputs()` / `allFormInputs()` | rendering / sync schema | dichiarare custom field |
| `beforeSubmit()` | prima del salvataggio | mutare/validare il payload |
| `afterSubmit()` | dopo il salvataggio | side-effect e integrazioni |
| `seo()` | rendering pagina | override di title/description/image |
