# Devil Fruit Archive

Plugin WordPress auto-contenuto (nessuna dipendenza esterna, nessun ACF)
per gestire un archivio/dossier in stile "Vegapunk Research Division" di
esemplari di Devil Fruit. **Non è un e-commerce**: niente carrello, niente
prezzo, niente "acquista" — solo un catalogo consultabile con un'unica CTA
("Richiedi questo esemplare") verso un contatto/DM esterno configurabile.

Brand: **FrancyStore3D**.

## Requisiti

- WordPress 6.0+
- PHP 7.4+

## Installazione / attivazione

1. Copia l'intera cartella del plugin (questa repo) dentro
   `wp-content/plugins/`, con nome cartella a tua scelta (es.
   `devil-fruit-archive`).
2. In wp-admin → **Plugin**, attiva **Devil Fruit Archive**.
3. All'attivazione il plugin registra il Custom Post Type `esemplare` e fa
   automaticamente il flush delle rewrite rules: l'archivio pubblico è
   subito raggiungibile su `/archivio/` senza dover risalvare i permalink
   a mano.
4. Se in seguito i permalink smettessero di funzionare (capita solo se il
   plugin viene disattivato/riattivato in modo anomalo), vai in
   **Impostazioni → Permalink** e clicca "Salva le modifiche" per forzare
   un nuovo flush.

## Creare un esemplare

1. In wp-admin, apri il menu **Devil Fruit Archive → Aggiungi esemplare**.
2. Inserisci un **Titolo** (uso interno/admin; il nome mostrato in scheda
   è il campo "Romaji").
3. Imposta l'**immagine in evidenza** (featured image): è la foto
   dell'esemplare nel barattolo, mostrata in primo piano nella scheda e
   nella card della griglia.
4. Compila il meta box **"Dati dell'esemplare (targa)"**:
   - `Catalog ID` (es. `DF-007`)
   - `Type` (LOGIA / PARAMECIA / ZOAN / ZOAN (MYTHICAL))
   - `Romaji` (es. `JIKI JIKI NO MI`)
   - `Katakana` (es. `ジキジキの実`)
   - `Special Note`
   - `Immagine frutto` (opzionale): immagine dedicata del frutto, mostrata
     in sovraimpressione in basso a destra sulla card della griglia
     archivio, sopra alla foto del proprietario usata come sfondo.
5. Compila il meta box **"Proprietari"**:
   - `Proprietario attuale` (testo) + relativa foto (bottone "Seleziona
     immagine", media uploader nativo di WordPress). Questa foto è
     usata come sfondo sia nella scheda singola sia nella card della
     griglia archivio.
   - `Ex proprietario` (opzionale) + relativa foto (opzionale), usata
     solo come riserva se la foto del proprietario attuale non è stata
     caricata.
6. Compila il meta box **"Research Note / Osservazioni"** con il testo di
   lore (mostrato in corsivo nella scheda).
7. Pubblica. L'esemplare è subito visibile su `/archivio/nome-esemplare/`
   e nella griglia `/archivio/`.

Nella lista **Devil Fruit Archive → Tutti gli esemplari** trovi le
colonne **Catalog ID** e **Type**, entrambe ordinabili cliccando
sull'intestazione.

## Impostazioni

**Devil Fruit Archive → Impostazioni**:

- **URL della CTA (DM / contatti)**: il link a cui punta il bottone
  "RICHIEDI QUESTO ESEMPLARE" su ogni scheda (es. un link a un DM
  Instagram, un modulo di contatto, un canale Discord). Se non impostato,
  il bottone punta a `#`.
- **Immagine di sfondo archivio**: immagine decorativa mostrata a piena
  larghezza in alto nella pagina archivio pubblica (`/archivio/`), dietro
  l'intestazione, con una dissolvenza verso il basso. Facoltativa; non
  compare nello shortcode `[devil_fruit_archive]` usato dentro una pagina
  del tema.
- **Sovrapposizione contenuto/immagine**: slider (0-400px) che definisce
  quanto intestazione e griglia "risalgono" sopra l'immagine di sfondo
  invece di iniziare sotto di essa in cascata.
- **Seed del catalogo**: vedi sotto.

Il numero di versione del plugin (bump ad ogni modifica) è mostrato in
fondo a questa pagina — utile per verificare a colpo d'occhio che un
aggiornamento sia stato effettivamente caricato sul sito.

## Seed del catalogo (17 esemplari di esempio)

Il file [`_seed/Devil_Fruit_Archive_Catalogo.md`](_seed/Devil_Fruit_Archive_Catalogo.md)
contiene 17 esemplari già scritti (catalog id, type, romaji, katakana,
special note, proprietari, lore), pronti per popolare l'archivio con
contenuti reali senza doverli scrivere a mano uno per uno.

**Dalla pagina impostazioni:**

1. Vai in **Devil Fruit Archive → Impostazioni**.
2. Nella sezione "Seed del catalogo" clicca **"Lancia il seed del
   catalogo"**.
3. Verrai reindirizzato alla lista esemplari con un messaggio che indica
   quanti esemplari sono stati creati e quanti già presenti (saltati).

**Da WP-CLI:**

```bash
wp devil-fruit-archive seed
```

Il seed è **idempotente**: se un esemplare con lo stesso `Catalog ID`
esiste già (in qualsiasi stato, anche bozza), la relativa scheda del
markdown viene saltata invece di creare un duplicato — puoi rilanciarlo
quante volte vuoi in sicurezza, anche dopo aver aggiunto altri esemplari
a mano.

**Le immagini non vengono importate dal seed**: dopo averlo lanciato,
apri ogni esemplare creato e carica manualmente dalla Libreria media
l'immagine in evidenza (esemplare nel barattolo) e le foto dei
proprietari.

## Griglia archivio e shortcode

- L'**archivio pubblico** con la griglia di tutti gli esemplari
  pubblicati è raggiungibile automaticamente su `/archivio/`.
- Per inserire la stessa griglia dentro **una pagina qualsiasi**, usa lo
  shortcode:

  ```
  [devil_fruit_archive]
  ```

  Attributo opzionale `per_page` per limitare il numero di card mostrate
  (default: tutti gli esemplari pubblicati):

  ```
  [devil_fruit_archive per_page="8"]
  ```

## Sovrascrivere i template dal tema

Il plugin carica due template di frontend, entrambi documenti HTML
completi e indipendenti dal tema (full-bleed, fedeli ai mockup in
`_design/`):

- `templates/single-esemplare.php` — scheda singola esemplare.
- `templates/archive-esemplare.php` — griglia archivio pubblico.

Per personalizzarli **senza modificare il plugin** (le modifiche dirette
ai file del plugin si perdono ad ogni aggiornamento), copia il file nel
tema attivo (o child theme) dentro una sottocartella
`devil-fruit-archive/`:

```
wp-content/themes/<il-tuo-tema>/devil-fruit-archive/single-esemplare.php
wp-content/themes/<il-tuo-tema>/devil-fruit-archive/archive-esemplare.php
```

Se il file esiste nel tema, il plugin lo usa al posto della propria
versione (stessa logica di `locate_template()` usata da WooCommerce e
da molti altri plugin WordPress). La card riusata dalla griglia
(`templates/parts/card-esemplare.php`) e gli stili in
`assets/css/devil-fruit-archive.css` restano invece quelli del plugin, a
meno di non enqueuare un CSS aggiuntivo dal tema per fare l'override.

## Struttura del plugin

```
devil-fruit-archive.php            File principale: header, versione, hook attivazione/disattivazione
includes/
  class-dfa-cpt.php                CPT "esemplare" (slug pubblico "archivio")
  class-dfa-meta.php               register_post_meta di tutti i campi
  class-dfa-metabox.php            Meta box admin + media uploader, salvataggio con nonce
  class-dfa-admin-columns.php      Colonne "Catalog ID" / "Type" nella lista admin, ordinabili
  class-dfa-shortcode.php          Shortcode [devil_fruit_archive]
  class-dfa-settings.php           Pagina impostazioni (URL CTA, bottone seed)
  class-dfa-seed.php               Parsing del markdown e creazione idempotente degli esemplari
  class-dfa-template-loader.php    Override dei template di frontend + enqueue asset
templates/
  single-esemplare.php             Scheda singola
  archive-esemplare.php            Griglia archivio
  parts/card-esemplare.php         Card riusata da griglia e shortcode
assets/
  css/devil-fruit-archive.css      Stile di frontend (derivato da _design/)
  css/admin-metabox.css            Stile minimo per i meta box
  js/devil-fruit-archive.js        JS minimo di frontend
  js/admin-metabox.js              Media uploader per le immagini owner nei meta box
_design/                           Mockup approvati (HTML/CSS) usati come base dei template
_seed/                             Catalogo dei 17 esemplari di esempio (sorgente del seed)
```

## Sicurezza

- Ogni output in frontend e in admin è escapato (`esc_html`, `esc_attr`,
  `esc_url`, `esc_textarea`).
- Ogni input salvato è sanitizzato (`sanitize_text_field`,
  `sanitize_textarea_field`, `esc_url_raw`, `absint`) sia lato
  `register_post_meta` sia esplicitamente nel salvataggio del meta box.
- Il salvataggio dei meta box è protetto da nonce (`wp_nonce_field` /
  `wp_verify_nonce`), controllo `current_user_can( 'edit_post', ... )` ed
  esclusione degli autosave.
- La pagina impostazioni usa la Settings API di WordPress
  (`register_setting`, nonce automatico via `options.php`) e richiede la
  capability `manage_options`.
- Il bottone di seed richiede `manage_options` ed è protetto da nonce
  (`check_admin_referer`).
- Nessuno schema Product, nessun prezzo, nessun carrello: il plugin non
  introduce funzionalità di e-commerce.
