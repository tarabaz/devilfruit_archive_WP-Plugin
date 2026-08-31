# Devil Fruit Archive

Plugin WordPress auto-contenuto (nessuna dipendenza esterna, nessun ACF)
per gestire un archivio/dossier in stile "Vegapunk Research Division" di
esemplari di Devil Fruit. **Non è un e-commerce**: niente carrello, niente
prezzo, niente "acquista" — solo un catalogo consultabile.

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
   dell'esemplare nel barattolo **con la lampada accesa**, mostrata in
   primo piano al centro della scheda singola. È lo stato iniziale della
   scheda. Non compare nella griglia archivio.
4. Compila il meta box **"Dati dell'esemplare (targa)"**:
   - `Catalog ID` (es. `DF-007`)
   - `Type` (LOGIA / PARAMECIA / ZOAN / ZOAN (MYTHICAL))
   - `Romaji` (es. `JIKI JIKI NO MI`)
   - `Katakana` (es. `ジキジキの実`)
   - `Special Note`
   - `Coming soon` (spunta): esemplare annunciato ma non ancora
     consultabile. Nella griglia archivio la card mostra la fascia
     COMING SOON in alto, **non è cliccabile** (non è un link, quindi
     non apre la scheda) e viene spostata **in fondo alla griglia**,
     anche se il suo Catalog ID verrebbe prima: se DF-002 e DF-005 sono
     normali e DF-004 è coming soon, l'ordine è DF-002, DF-005, DF-004.
     La card è interamente in **bianco e nero** (frutto e icona della
     tipologia compresi) e al passaggio del mouse non cambia nulla: non
     si illumina e non si colora, per non far pensare che sia apribile.
   - `Immagine frutto`: immagine "prodotto" mostrata nel riquadro della
     card nella griglia archivio (al posto della featured image; se non
     caricata quel riquadro resta trasparente e mostra lo sfondo dietro,
     invece di un blocco nero) e, nella scheda singola, nella targa a
     destra del nome. Se non caricata, la targa resta come prima.
   - `Immagine esemplare a lampada spenta` (opzionale): stessa
     inquadratura dell'immagine in evidenza ma con la lampada del
     barattolo spenta. Se valorizzata, nella scheda singola compare
     sotto l'esemplare un bottone `SPEGNI LA LAMPADA` / `ACCENDI LA
     LAMPADA` che alterna le due immagini con una dissolvenza di 1
     secondo. Se il campo è vuoto il bottone non viene mostrato.
     La scheda **parte con la lampada accesa**, quindi il bottone parte
     da "Spegni". La chiave meta si chiama ancora `dfa_specimen_lit_image`
     per non invalidare contenuti e pacchetti di export già esistenti.
5. Compila il meta box **"Proprietari"**:
   - `Proprietario attuale` (testo) + relativa foto (bottone "Seleziona
     immagine", media uploader nativo di WordPress). Questa foto è usata
     come **sfondo a piena pagina della scheda singola** e come sfondo
     della card nella griglia archivio, lì in bianco e nero (torna a
     colori al passaggio del mouse sulla card).
   - `Ex proprietario` (opzionale): solo testo, popola la riga "EX
     PROPRIETARIO" nella targa. Nessuna immagine dedicata.
6. Compila il meta box **"Research Note / Osservazioni"** con il testo di
   lore (mostrato accanto alla targa nella scheda, stesso font della targa).
7. Pubblica. L'esemplare è subito visibile su `/archivio/nome-esemplare/`
   e nella griglia `/archivio/`.

## La lista degli esemplari in wp-admin

**Devil Fruit Archive → Tutti gli esemplari** mostra le colonne
**Catalog ID**, **Type**, **Proprietario**, **Ex proprietario** e
**Pubblicato**.

- La lista è ordinata per **Catalog ID crescente** (DF-001, DF-002, ...)
  senza dover cliccare nulla. Cliccando l'intestazione di Catalog ID,
  Type, Titolo o Data si ordina come al solito per quella colonna.
- La colonna **Pubblicato** è una spunta: cliccarla pubblica o
  spubblica l'esemplare all'istante, senza aprire il post e senza
  ricaricare la pagina. Se qualcosa va storto (permessi, sessione
  scaduta) la spunta torna com'era e compare il motivo.
- La colonna **Coming soon** funziona allo stesso modo e marca
  l'esemplare come annunciato ma non ancora consultabile (vedi sopra:
  fascia COMING SOON, card non cliccabile, in fondo alla griglia).
- Entrambe le spunte sono disponibili anche nelle **Modifiche rapide**.
  Quella di Pubblicato è collegata al menu "Stato" nativo di WordPress:
  spuntarla equivale a scegliere "Pubblicato", toglierla equivale a
  "Bozza".
- Nella lista admin i "coming soon" **non** vengono spostati in fondo:
  restano al loro numero, perché in redazione serve trovarli lì.
- Per gli stati che una spunta non sa rappresentare (in attesa di
  revisione, programmato, cestino) la colonna mostra l'etichetta dello
  stato invece della spunta, così un clic non può stravolgerlo. Lo
  stesso vale per chi non ha i permessi di modifica su quell'esemplare.

## Impostazioni

**Devil Fruit Archive → Impostazioni**:

- **URL della CTA (DM / contatti)**: impostazione al momento **non
  utilizzata**. Il bottone "RICHIEDI QUESTO ESEMPLARE" è stato rimosso
  dalla scheda singola, dove al suo posto c'è l'interruttore della
  lampada. Il campo resta disponibile per riattivare in futuro una CTA
  di contatto senza doverla riconfigurare.
- **Immagine di sfondo archivio**: immagine decorativa in cima alla
  pagina archivio pubblica (`/archivio/`), dietro al contenuto, che le
  scorre sopra partendo a 50px dal bordo superiore. È mostrata alla sua
  **dimensione naturale**, ancorata al centro in orizzontale e in alto
  in verticale: su schermi più stretti dell'immagine viene ritagliata ai
  lati, su schermi più larghi resta della sua misura con il fondo scuro
  attorno. Regola quindi la larghezza del file che carichi in base alla
  risoluzione che ti interessa coprire. Facoltativa; non compare nello
  shortcode `[devil_fruit_archive]` usato dentro una pagina del tema.
- **Sfondo di riserva scheda singola**: usato come sfondo sulle schede
  degli esemplari che non hanno ancora una `Foto proprietario attuale`
  caricata.
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

## Esporta / Importa l'archivio (backup e trasferimento)

In **Devil Fruit Archive → Impostazioni**, sezione "Esporta / Importa
archivio", trovi due pulsanti che gestiscono l'intero contenuto del
plugin in un unico file `.zip`.

**Cosa contiene il pacchetto**

```
archivio.json      gli esemplari del pacchetto (titolo, stato e ogni
                   campo) + le impostazioni del plugin (solo nel primo)
images/…           i file immagine originali: foto esemplare (featured),
                   versione accesa, immagine frutto, foto proprietario
                   e le due immagini di sfondo delle impostazioni
```

**Esportare**: il backup è **diviso in pacchetti da 10 esemplari**, in
ordine di Catalog ID. La pagina impostazioni ne elenca uno per riga con
etichetta (`1-10`, `11-20`, `21-23`…), numero di esemplari e peso
stimato, e un bottone "Scarica" per ciascuno. I file si chiamano
`devil-fruit-archive-1-10-AAAA-MM-GG-HHMMSS.zip`.

Serve perché l'archivio intero diventa presto un file troppo grande per
essere ricaricato: con foto in alta risoluzione si superano i 100 MB con
una ventina di esemplari, e il limite di caricamento del server (spesso
64 o 128 MB) blocca proprio l'importazione, cioè quando il backup
servirebbe. Se un pacchetto supera comunque il limite del server, la
riga lo segnala in rosso.

I pacchetti sono **indipendenti** e si importano uno alla volta in
qualsiasi ordine: l'import aggiorna gli esemplari già presenti (Catalog
ID) e riusa le immagini già in Libreria (impronta del file). Le
impostazioni del plugin, con le due immagini di sfondo, viaggiano solo
nel **primo** pacchetto.

**Importare**: seleziona un pacchetto e clicca "Importa dal pacchetto".

L'importazione avviene **a lotti, con una barra di avanzamento**: il
caricamento del file e l'estrazione avvengono subito, poi la pagina
esegue il lavoro qualche secondo per volta finché non arriva al 100%.
Serve perché la parte lenta non sono i post (millisecondi l'uno) ma le
immagini: ognuna viene ricaricata nella Libreria media e rigenerata in
tutti i formati del sito, un'operazione da uno o più secondi a immagine.
Su un pacchetto di qualche decina di MB, farlo in un'unica richiesta
supera qualunque `max_execution_time` e l'importazione fallisce a metà.

- **Lascia la pagina aperta** finché la barra non arriva in fondo: è il
  browser a chiedere un lotto dopo l'altro.
- Le immagini **non vengono duplicate**: ogni allegato creato
  dall'importazione viene marchiato con l'impronta (md5) del file, e se
  un pacchetto successivo contiene lo stesso file si riusa l'allegato
  già in Libreria invece di caricarne una copia. Il confronto è sul
  contenuto e non sul nome, perché WordPress rinomina i file in
  conflitto (`foto-1.jpg`, `foto-2.jpg`). Vale dalla versione 3.9 in
  poi: le copie create dalle importazioni precedenti restano dov'erano e
  vanno eventualmente cestinate a mano.
- Se chiudi a metà, la volta successiva l'importazione riparte da capo:
  la cartella di lavoro rimasta viene ripulita all'avvio della nuova.
  Non si creano duplicati, perché l'import è idempotente sul Catalog ID.
- Il limite di dimensione del file è quello del server (`upload_max_filesize`
  e `post_max_size` di PHP): la pagina delle impostazioni lo mostra sotto
  il campo di caricamento. Se il pacchetto è più grande, l'unica strada è
  alzare quei valori lato hosting.

- L'import è **idempotente sul Catalog ID**: un esemplare già presente
  viene aggiornato, non duplicato. Puoi quindi reimportare lo stesso
  pacchetto più volte in sicurezza.
- Le immagini vengono **ricaricate nella Libreria media** del sito di
  destinazione e ricollegate ai campi corretti: il pacchetto è
  trasferibile fra siti diversi, dove gli ID allegato di origine non
  avrebbero alcun significato. Un'immagine condivisa da più esemplari
  viene caricata una volta sola.
- Le impostazioni del plugin contenute nel pacchetto **sovrascrivono**
  quelle attuali.
- A fine operazione un messaggio riepiloga quanti esemplari sono stati
  creati, quanti aggiornati e quante immagini caricate.

**Requisito**: l'estensione PHP `zip` (ZipArchive) dev'essere attiva sul
server. Se manca, la sezione lo segnala e i due pulsanti non vengono
mostrati.

**Nota sui limiti di upload**: se l'archivio contiene molte immagini, il
pacchetto può superare la dimensione massima di caricamento del server
(`upload_max_filesize` / `post_max_size`). In quel caso l'import segnala
l'errore: chiedi all'hosting di alzare quei limiti.

Al passaggio del mouse su una card **attiva** il velo nero si schiarisce
del 10% (opacità da .5 a .45), il personaggio passa a colori e il frutto
si illumina. La card non si solleva: resta ferma, cambiano solo bordo e
alone.

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
  class-dfa-admin-columns.php      Colonne della lista admin (Catalog ID, Type, proprietari,
                                   spunta Pubblicato), ordine per Catalog ID, quick edit
  class-dfa-shortcode.php          Shortcode [devil_fruit_archive]
  class-dfa-settings.php           Pagina impostazioni (URL CTA, bottone seed)
  class-dfa-seed.php               Parsing del markdown e creazione idempotente degli esemplari
  class-dfa-transfer.php           Export/import completo in .zip (dati, impostazioni e file immagine)
  class-dfa-template-loader.php    Override dei template di frontend + enqueue asset
templates/
  single-esemplare.php             Scheda singola
  archive-esemplare.php            Griglia archivio
  parts/card-esemplare.php         Card riusata da griglia e shortcode
assets/
  css/devil-fruit-archive.css      Stile di frontend (derivato da _design/)
  css/admin-metabox.css            Stile minimo per i meta box
  css/admin-list.css               Stile della colonna "Pubblicato" nella lista admin
  js/devil-fruit-archive.js        JS minimo di frontend
  js/admin-metabox.js              Media uploader per le immagini owner nei meta box
  js/admin-list.js                 Spunta "Pubblicato" (AJAX) e spunta nelle Modifiche rapide
  js/admin-import.js               Importazione a lotti con barra di avanzamento
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
- La spunta "Pubblicato" della lista admin passa da admin-ajax e prima
  di cambiare stato verifica, in quest'ordine: nonce
  (`check_ajax_referer`), esistenza del post e post type corretto,
  `current_user_can( 'edit_post', ... )`, la capability di pubblicazione
  del post type quando si pubblica, e che lo stato di partenza sia
  "pubblicato" o "bozza".
- Nessuno schema Product, nessun prezzo, nessun carrello: il plugin non
  introduce funzionalità di e-commerce.
