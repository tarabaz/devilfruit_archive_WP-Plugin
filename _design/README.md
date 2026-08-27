# _design — mockup approvati

Materiale di design per il plugin "Devil Fruit Archive", esportato dal canvas
Claude Design `Devil Fruit Archive.dc.html` (4 schermate: scheda singola
desktop, archivio/home, scheda mobile, variante scheda a proprietario unico).

- `style.css` — design system: variabili CSS (`--bg`, `--panel`, `--silver`,
  `--line`), font (Saira Condensed, IBM Plex Mono, Noto Sans JP) e classi
  `dfa-single__*` / `dfa-archive__*` usate 1:1 dai template PHP del plugin
  (`assets/css/devil-fruit-archive.css` è la copia di produzione di questo
  file).
- `single-esemplare.html` — mockup statico della scheda singola (variante a
  due proprietari: attuale + ex). La variante a proprietario singolo si
  ottiene omettendo il secondo `<img>` di sfondo e il blocco "EX
  PROPRIETARIO" (gestito nel template con `sc-if`/condizionali PHP).
  Il comportamento mobile è gestito via media query nello stesso CSS
  (schermata 03 del canvas), non con un file HTML separato.
- `archive-esemplare.html` — mockup statico della griglia archivio (4
  colonne desktop, responsive), riusato sia dal template `archive-esemplare.php`
  sia dallo shortcode `[devil_fruit_archive]`.
- `reference-assets/` — immagini placeholder generate dal tool di design
  (`image-slot`) usate solo per la messa a punto visiva dei mockup. **Non**
  vengono distribuite con il plugin: le foto reali (esemplare in barattolo,
  arte del proprietario) si caricano dalla Libreria media di WordPress
  esemplare per esemplare.

I template PHP in `templates/` replicano fedelmente struttura e classi di
questi mockup, sostituendo i testi statici con i campi dinamici del CPT
`esemplare` (vedi meta box in `includes/`).
