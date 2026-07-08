# Skill 06 — CSS i UI

## Objectiu

Refactoritzar i ampliar el CSS d’**Entorns de Natura** de manera sostenible, mantenint el resultat visual actual sempre que sigui possible i millorant la claredat del codi.

Aquest skill s’ha de consultar abans de:

- reorganitzar `public/assets/css/styles.css`;
- crear o modificar components visuals;
- reduir duplicació d’estils;
- afegir variables CSS;
- canviar classes, noms de components o estructura visual;
- revisar responsive, focus, hover o accessibilitat.

---

## Fitxer principal

El CSS que carrega l’aplicació és:

```text
public/assets/css/styles.css
```

No posar CSS necessari per la web dins:

```text
assets/css/
css/
```

Aquests directoris poden existir com a referència històrica, però el navegador no els carrega des de l’aplicació PHP modular.

---

## Principis

Prioritats:

1. Mantenir funcionalitat.
2. Evitar canvis visuals grans no demanats.
3. Reduir duplicació.
4. Fer el CSS més llegible.
5. Preparar components reutilitzables.
6. Millorar accessibilitat.

No fer una reescriptura completa si una refactorització incremental resol el problema.

---

## Flux de treball recomanat

Abans de tocar CSS:

1. Buscar on s’utilitzen les classes.
2. Revisar la vista PHP relacionada.
3. Identificar si el canvi afecta web pública, alumne, professor o admin.
4. Separar canvis visuals de canvis estructurals.
5. Fer canvis petits i verificables.

Comandes útils:

```bash
rg -n "nom-classe|component" resources public app
rg -n "#[0-9a-fA-F]{3,6}|rgba|border-radius|box-shadow|padding|margin" public/assets/css/styles.css
```

---

## Organització del CSS

Ordenar el CSS per seccions clares:

```css
/* Variables globals */
/* Base */
/* Layout */
/* Components */
/* Formularis */
/* Taules */
/* Admin */
/* Estats */
/* Responsive */
```

Els comentaris han d’ajudar a localitzar blocs, no explicar cada propietat.

---

## Variables CSS

Centralitzar valors repetits a `:root`.

Variables recomanades:

```css
:root {
    --color-text: #1f2d1f;
    --color-muted: #6d7a6b;
    --color-primary: #2f5d3a;
    --color-primary-soft: #e7efdf;
    --color-surface: #ffffff;
    --color-page: #f4f8f2;
    --color-border: #dfe9d8;
    --color-success: #2f5d3a;
    --color-error: #9f3a38;
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --shadow-sm: 0 2px 10px rgba(0,0,0,.08);
    --shadow-md: 0 8px 24px rgba(47,93,58,.08);
    --space-xs: .35rem;
    --space-sm: .5rem;
    --space-md: 1rem;
    --space-lg: 1.5rem;
    --transition-fast: .15s ease;
}
```

Afegir variables quan hi hagi repetició real. No crear variables per valors que apareixen una sola vegada sense motiu clar.

---

## Reduir duplicació

Buscar i agrupar:

- colors repetits;
- `border-radius`;
- ombres;
- paddings de targetes;
- estils de botons;
- estils de formularis;
- estats `hover`, `focus`, `disabled`;
- patrons de graella.

Exemple de millora:

```css
.card,
.auth-card,
.project-detail {
    background: var(--color-surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}
```

No agrupar selectors si això crea dependències confuses o si els components poden evolucionar de manera diferent.

---

## Nomenclatura

Preferir classes descriptives i consistents.

Patró recomanat inspirat en BEM:

```css
.project-card {}
.project-card__header {}
.project-card__logo {}
.project-card__actions {}
.project-card--inactive {}
```

Per components existents, no canviar noms de classe només per “fer-ho més BEM” si això obliga a tocar moltes vistes sense benefici clar.

Quan es canviï una classe:

1. Actualitzar totes les vistes que la fan servir.
2. Revisar JavaScript si depèn d’aquella classe.
3. Verificar amb `rg`.

---

## Selectors

Evitar:

```css
main div section article div span {}
#projectes-lista .card .foo {}
```

Preferir:

```css
.project-card__status {}
.admin-subsection {}
```

Evitar estils acoblats a IDs excepte per ancoratges o casos molt concrets.

---

## Accessibilitat

Mantenir o afegir:

- contrast suficient entre text i fons;
- `:focus-visible` en botons, enllaços i camps;
- estats `hover` que no siguin l’única pista visual;
- mides de clic còmodes;
- formularis llegibles en mòbil.

Exemple:

```css
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 3px solid rgba(47,93,58,.35);
    outline-offset: 2px;
}
```

No eliminar estils que ajudin a entendre focus, errors o estats inactius.

---

## Responsive

Agrupar media queries quan sigui raonable.

Evitar duplicar moltes regles petites si poden conviure en una sola secció responsive.

Criteris:

- el panell admin ha de ser escanejable en desktop;
- les taules poden tenir `overflow-x: auto`;
- targetes i formularis han de passar a una columna en mòbil;
- text i botons no s’han de solapar.

---

## Canvis visuals

Mantenir el look actual si l’usuari demana refactor tècnic.

Si es detecta una incoherència visual important:

- aplicar-la directament només si és petita i segura;
- proposar-la abans si canvia jerarquia, colors dominants, layout principal o comportament.

Exemples de canvis segurs:

- substituir colors repetits per variables equivalents;
- unificar radi de targetes semblants;
- afegir `focus-visible`;
- compactar CSS duplicat sense canviar HTML.

Exemples que cal proposar abans:

- canviar tota la paleta;
- redissenyar el dashboard;
- substituir taules per targetes;
- canviar l’estructura HTML de moltes vistes.

---

## Verificació

Després de tocar CSS:

1. Fer `rg` per assegurar que les classes canviades existeixen.
2. Validar que el JavaScript no depèn de classes eliminades.
3. Si s’han tocat vistes PHP, executar lint:

```bash
/Applications/XAMPP/xamppfiles/bin/php -l resources/views/admin/dashboard.php
```

4. Si s’ha tocat JS relacionat:

```bash
node --check public/assets/js/scripts.js
```

5. Revisar visualment les pantalles afectades quan sigui possible.

---

## Resultat esperat

En acabar una refactorització CSS, explicar breument:

- quins fitxers s’han canviat;
- quines duplicacions s’han reduït;
- quines variables s’han creat o reutilitzat;
- quin impacte visual hi ha;
- quines recomanacions queden pendents.

Resposta curta i concreta. No fer un informe llarg si el canvi és petit.
