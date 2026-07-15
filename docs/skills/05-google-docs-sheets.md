# Skill 05 — Google Docs i Google Sheets

## Objectiu

Preparar el projecte **Entorns de Natura** per sincronitzar continguts i dades des de Google Workspace.

Google Docs i Google Sheets poden funcionar com a espai de treball del professorat.

La web ha de mostrar aquesta informació de manera controlada, segura i estructurada.

## Estat actual

### Implementat

- configuració base a `config/google.php`;
- servei `GoogleSyncService` present, encara molt inicial;
- estructura de base preparada per a sincronització i importació;
- model de dades per a projectes, assets i avaluació ja existent;
- quan el contingut és d'una edició concreta, la unitat funcional és `project_academic_years`.

### Encara previst

- integració real amb API de Google;
- taules de fonts i execucions de sincronització;
- importació robusta de Docs i Sheets;
- validació, logs i reprocessament d'errors;
- definició del flux entre document origen, BD i vista final.

---

## Flux general previst

```text
Google Docs / Google Sheets
        ↓
Servei PHP de sincronització
        ↓
Base de dades MySQL/MariaDB
        ↓
Web pública / alumnat / professorat / administració
```

---

## Principi general

Google és l’espai d’edició del professorat.

La base de dades és l’espai controlat des d’on la web mostra informació.

`project_id` continua sent útil per al catàleg base del projecte, però quan el contingut depèn d'un curs o d'una edició concreta cal partir de `project_academic_year_id`.

Quan un contingut tingui diferents nivells de visibilitat, la recomanació és classificar-lo per context i no publicar directament el document original sense control.

---

## Google Docs

Ús previst:

- programacions;
- guies didàctiques;
- textos públics dels projectes;
- materials d’alumnes;
- materials de professorat;
- documents multilingües.

Nivells de visibilitat recomanats:

```text
public
students
teachers
assigned_teachers
admin
```

Exemples:

- `public`: presentació i organització general;
- `students`: bastides, ajudes i materials d'alumnat;
- `teachers`: programacions i visió general;
- `assigned_teachers`: programacions completes, terminis i materials interns;
- `admin`: contingut i control total.

Opcions:

```text
1. Incrustar document directament.
2. Llegir document amb API i mostrar-lo.
3. Sincronitzar document a la base de dades.
```

---

## Quan no cal base de dades

No cal base de dades si el document és:

- públic;
- simple;
- només informatiu;
- sense dades sensibles;
- sense necessitat de filtres;
- sense necessitat de permisos interns.

Exemples:

```text
document públic de presentació
guia pública
document de difusió
```

---

## Quan sí convé base de dades

Sí convé base de dades si el document és:

- intern;
- sensible;
- reutilitzable;
- multilingüe;
- relacionat amb projectes;
- amb visibilitat segons rol;
- necessari per dashboards;
- necessari per historial o control de versions.

Exemples:

```text
programacions internes
materials de professorat
continguts d’alumnat amb permisos
documents per projecte i idioma
```

---

## Google Sheets

Ús previst:

- rúbriques;
- dades de camp;
- llistats;
- calendaris;
- registres;
- dades científiques;
- dades agregades;
- importacions d’alumnes o grups.

Per a dades d'aula i avaluació, la regla és més estricta: el Sheet pot ser font d'entrada, però la publicació final ha de passar per la base de dades i pels permisos del sistema.

---

## Quan no cal base de dades amb Sheets

No cal base de dades si el Sheet és:

- públic;
- simple;
- sense dades personals;
- sense dades sensibles;
- només visual;
- sense dashboards avançats.

Exemples:

```text
calendari públic
taula pública de resultats agregats
llistat públic de recursos
```

---

## Quan sí cal base de dades amb Sheets

Sí cal base de dades si el Sheet conté:

- alumnes;
- notes;
- rúbriques avaluades;
- observacions;
- dades d’aula;
- dades privades;
- dades filtrades per usuari;
- dades per dashboards;
- dades que cal validar;
- dades que necessiten historial.

Exemples:

```text
notes d’alumnes
rúbriques
seguiment de grups
dades científiques per gràfiques
observacions de professorat
```

---

## Taules existents

```text
google_sources
synced_documents
synced_sheet_rows
google_sync_runs
google_sync_errors
```

---

## `google_sources`

Registra documents o fulls de Google vinculats a una edició concreta de projecte.

Camps:

```text
id
project_academic_year_id
source_type
google_file_id
google_file_url
sheet_name
range_name
language_code
content_type
visibility
sync_mode
is_active
last_synced_at
created_at
updated_at
```

Tipus de font:

```text
google_doc
google_sheet
google_drive_file
```

Tipus de contingut:

```text
programacio
material_alumnes
material_professorat
rubrica
dades_camp
calendari
noticies
```

Visibilitat:

```text
public
students
teachers
admin
```

Mode de sincronització:

```text
manual
automatic
disabled
```

---

## `synced_documents`

Guarda contingut processat de Google Docs.

Camps:

```text
id
google_source_id
project_academic_year_id
language_code
title
content_html
plain_text
version_hash
synced_at
```

La clau funcional ha de ser `google_source_id + language_code`, i la dada ha d'anar lligada a `project_academic_year_id`.

---

## `synced_sheet_rows`

Guarda files importades de Google Sheets.

Camps:

```text
id
google_source_id
project_academic_year_id
external_id
row_number
row_data_json
row_hash
is_active
synced_at
```

Aquesta taula es pot usar com a zona intermèdia abans de transformar les dades cap a taules finals.

---

## `google_sync_runs`

Registra cada execució de sincronització.

Camps:

```text
id
google_source_id
project_academic_year_id
started_by_user_id
started_at
finished_at
status
rows_read
rows_created
rows_updated
rows_skipped
errors_count
message
```

Estats:

```text
pending
running
completed
completed_with_warnings
failed
```

---

## `google_sync_errors`

Registra errors de sincronització.

Camps:

```text
id
google_sync_run_id
project_academic_year_id
row_number
field_name
error_message
raw_value
created_at
```

---

## Sincronització recomanada

Fase inicial:

```text
botó manual de sincronització
```

Fase posterior:

```text
sincronització automàtica amb cron
```

Fase avançada:

```text
Apps Script o notificacions des de Google
```

---

## Validació de dades

Abans d’importar dades d’un Sheet cal validar:

- columnes esperades;
- emails;
- dates;
- números;
- duplicats;
- camps obligatoris;
- relació amb projectes;
- relació amb classes;
- dades buides;
- formats incorrectes.

Per a contingut contextual, la validació ha de partir de `project_academic_year_id`.

---

## Identificador estable

Cada fila important d’un Sheet hauria de tenir un identificador estable.

Exemples:

```text
id_mostra
id_criteri
id_alumne
id_registre
external_id
```

Si no hi ha identificador, es pot utilitzar una combinació controlada de camps, però és menys recomanable.

---

## Estratègia d’importació

Estratègia recomanada:

```text
1. Llegir dades del Sheet.
2. Guardar files a synced_sheet_rows.
3. Validar dades.
4. Transformar dades.
5. Actualitzar taules finals.
6. Registrar sync_run.
7. Registrar errors si n’hi ha.
```

---

## Normes de seguretat

- No posar credencials de Google al JavaScript.
- No posar claus de Google dins el repositori.
- No publicar fitxers de credencials.
- No mostrar dades privades directament des de Google Sheets.
- No publicar notes ni dades d’alumnes.
- Validar sempre abans d’importar.
- Guardar logs de sincronització.
- No esborrar dades automàticament sense control.

---

## Criteri principal

Google Docs i Google Sheets poden ser fonts de treball.

La web ha de mostrar dades des de la base de dades quan calgui seguretat, permisos, filtres, dashboards o historial.

La integració amb Google s’ha de fer des del servidor PHP, no des del navegador.
