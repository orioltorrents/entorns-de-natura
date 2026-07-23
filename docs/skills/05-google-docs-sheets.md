# Skill 05 — Google Docs i Google Sheets

## Objectiu

Preparar el projecte **Entorns de Natura** per sincronitzar continguts i dades des de Google Workspace.

Google Docs i Google Sheets poden funcionar com a espai de treball del professorat.

La web ha de mostrar aquesta informació de manera controlada, segura i estructurada.

## Estat actual

### Implementat

- configuració base a `config/google.php`;
- servei `GoogleSyncService` present com a stub de consulta, sense connexió real amb l'API;
- estructura de base preparada per a fonts, documents sincronitzats, files importades, execucions i errors;
- importació manual JSON de documents amb `DocumentSyncController` i `DocumentImportService`;
- model de dades per a projectes, assets i avaluació ja existent;
- quan el contingut és d'una edició concreta, la unitat funcional és `project_academic_years`.

### Encara previst

- integració real amb API de Google;
- OAuth o compte de servei i gestió de scopes;
- importació robusta directa de Docs i Sheets;
- sanitització HTML, límits d'importació, logs, reintents i reprocessament d'errors;
- definició definitiva del flux entre document origen, `google_sources`, `document_sources`, BD i vista final;
- sincronització automàtica amb cron o worker.

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

## Nivells actuals

L'estat del projecte s'ha de llegir en tres nivells diferents:

```text
1. Importació manual JSON de documents → implementada.
2. Persistència Google Workspace       → taules preparades.
3. Integració real amb API de Google   → pendent.
```

La importació manual JSON no és encara sincronització amb l'API de Google. Serveix per carregar documents, fonts, fragments i regles de visibilitat a partir d'un payload controlat.

---

## Principi general

Google és l’espai d’edició del professorat.

La base de dades és l’espai controlat des d’on la web mostra informació.

`project_id` continua sent útil per al catàleg base del projecte, però quan el contingut depèn d'un curs o d'una edició concreta cal partir de `project_academic_year_id`.

Quan un contingut tingui diferents nivells de visibilitat, la recomanació és classificar-lo per context i no publicar directament el document original sense control.

`GoogleSyncService` actualment només recupera fonts actives per `project_academic_year_id` i retorna un resultat `pending` a `syncProjectAcademicYear()`. No llegeix Google Docs ni Google Sheets, no crea `google_sync_runs`, no registra errors i no actualitza `last_synced_at`.

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
google_documents
google_document_blocks
google_sheet_rows
google_sync_runs
google_sync_errors
```

Aquestes taules ja existeixen. El que està pendent és fer-les servir en una sincronització real amb l'API.

També existeix la capa de documents pròpia de l'aplicació:

```text
documents
document_sources
document_fragments
document_visibility_rules
```

`document_sources` descriu fonts associades als documents interns de l'aplicació. `google_sources` descriu possibles fonts de Google Workspace per edició de projecte. La relació definitiva entre totes dues capes encara és una decisió pendent.

Regla clau: les taules `google_*` no substitueixen les taules `documents_*`.

```text
google_*    -> capa d'origen i sincronització amb Google Workspace
documents_* -> capa interna publicable de l'aplicació
```

El contingut de Google Docs no s'ha de mostrar directament a la web final sense validació, sanitització i transformació quan calgui. La capa `documents`, `document_fragments` i `document_visibility_rules` continua sent la font que l'aplicació pot publicar, filtrar i mostrar segons permisos.

Flux recomanat per Docs:

```text
Google Docs
→ google_sources
→ google_documents
→ google_document_blocks
→ documents / document_fragments / document_visibility_rules
→ web pública / alumnat / professorat / administració
```

Flux recomanat per Sheets:

```text
Google Sheets
→ google_sources
→ google_sheet_rows
→ assessment_records o altres taules finals
→ web privada o dashboards
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
assigned_teachers
admin
```

Nota de model: `google_sources.visibility` utilitza valors en plural (`students`, `teachers`, `assigned_teachers`). La capa interna de documents utilitza valors relacionats però no idèntics: `documents.default_visibility` fa servir `student`, `teacher` i `assigned_teacher`, i `document_visibility_rules.visibility_type` diferencia `public`, `role`, `project_role`, `class` i `assigned_teacher`. Aquesta diferència s'ha de tenir present en qualsevol transformació entre Google i documents interns.

Mode de sincronització:

```text
manual
automatic
disabled
```

---

## `google_documents`

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
is_active
created_at
updated_at
```

La clau funcional ha de ser `google_source_id + language_code`, i la dada ha d'anar lligada a `project_academic_year_id`.

---

## `google_document_blocks`

Guarda blocs o fragments processats d'un document sincronitzat.

Camps:

```text
id
google_document_id
google_source_id
project_academic_year_id
visibility_level
section_title
slug
content_html
display_order
is_active
created_at
updated_at
```

La clau funcional recomanada és `google_document_id + slug`. La taula manté també `google_source_id` i `project_academic_year_id` per facilitar filtres de lectura i coherència amb l'edició de projecte.

---

## `google_sheet_rows`

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
created_at
updated_at
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
importació manual JSON de documents
```

Fase posterior:

```text
sincronització automàtica amb cron
```

Fase avançada:

```text
Apps Script o notificacions des de Google
```

Components actuals de la fase inicial:

- `DocumentSyncController`: protegeix la pantalla amb rol `admin`, rep el JSON i mostra resultat o error;
- `DocumentImportService`: valida l'estructura bàsica del payload i importa documents, fonts, fragments i regles;
- `resources/views/admin/document-sync.php`: formulari d'entrada manual del JSON.

Limitació actual: aquesta importació manual no té CSRF propi i no defineix límits explícits de mida del payload.

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
2. Guardar files a google_sheet_rows.
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

## Backlog pendent

Aquests punts no estan resolts encara i formen part de l'evolució prevista:

- implementar OAuth o compte de servei amb scopes mínims;
- llegir contingut real de Google Docs i Google Sheets des del servidor;
- sanititzar `content_html` abans de publicar-lo;
- definir límits de mida per importacions manuals i automàtiques;
- afegir CSRF al formulari d'importació manual;
- registrar `google_sync_runs` i `google_sync_errors` durant sincronitzacions reals;
- definir política de reintents i reprocessament d'errors;
- automatitzar sincronització amb cron, worker o mecanisme equivalent;
- decidir la relació definitiva entre `google_sources` i `document_sources`;
- normalitzar o mapar explícitament les visibilitats singulars i plurals.

---

## Criteri principal

Google Docs i Google Sheets poden ser fonts de treball.

La web ha de mostrar dades des de la base de dades quan calgui seguretat, permisos, filtres, dashboards o historial.

La integració amb Google s’ha de fer des del servidor PHP, no des del navegador.
