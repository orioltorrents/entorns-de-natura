# Skill 02 — Base de dades

## Objectiu

Gestionar correctament la base de dades MySQL/MariaDB del projecte **Entorns de Natura**.

La base de dades ha de permetre gestionar:

- usuaris;
- rols;
- classes;
- alumnes;
- professorat;
- projectes;
- assets, softwares i apps associats a projectes;
- idiomes;
- assignacions de projectes;
- futura sincronització amb Google;
- futures rúbriques i notes.

## Estat actual

### Implementat

- connexió amb PDO des de `config/database.php`;
- esquema educatiu base amb usuaris, rols, classes, projectes, idiomes i assignacions;
- `project_academic_years` com a unitat funcional per a dades d'edició;
- taules d'avaluació i estructura de fases i tasques;
- catàleg d'assets de projecte i relacions amb projectes;
- capa de documents, fragments i visibilitat;
- documents lligats a `project_academic_years`;
- notes i imports d'avaluació lligats a `project_academic_year_id`;
- capa de Google Workspace preparada amb taules pròpies lligades a `project_academic_years`;
- seccions de projecte i permisos per rol;
- taula d'analítica de visites `site_visits`.

### Encara previst

- integració real amb Google Workspace;
- taules de rúbriques i notes definitives;
- possibles extensions de visibilitat i historial si calen més endavant.

---

## Base de dades local

Nom de la base de dades local:

```text
entorns_de_natura
```

La connexió llegeix el valor efectiu de `DB_NAME` a `.env`. Per a l'esquema i els procediments de reconstrucció, la font canònica és `database/README.md`; la seqüència executable és `database/schema.sql`.

Entorn:

```text
XAMPP + MySQL/MariaDB + phpMyAdmin
```

---

## Connexió

La connexió es fa amb PDO des de:

```text
config/database.php
```

Les dades de connexió es llegeixen des de:

```text
.env
```

Exemple:

```env
DB_HOST=localhost
DB_NAME=entorns_de_natura
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

---

## Taules actuals

L'esquema final inclou:

```text
users
student_profiles
web_roles
user_web_roles
project_roles
languages
academic_years
classes
class_members
class_member_history
class_teachers
site_visits
settings

projects
project_translations
project_academic_years
project_class_assignments
project_assets
project_asset_links

project_teams
project_team_members
project_team_member_roles

assessment_sources
assessment_import_runs
assessment_records
assessment_import_errors
assessment_phases
assessment_tasks
project_academic_year_phases
project_academic_year_phase_tasks
assessment_supports
assessment_task_resources

documents
document_sources
document_fragments
document_visibility_rules

project_sections
project_section_roles

google_sources
google_documents
google_document_blocks
google_sheet_rows
google_sync_runs
google_sync_errors
```

La taula `site_visits` es garanteix des del servei d'analítica si encara no existeix.

`project_groups` és un nom legacy. El model actual utilitza `project_class_assignments`; `database/12_project_class_assignments.sql` només conserva la conversió per a bases antigues.

## Reconstrucció i migracions

Per reconstruir una base neta, cal executar:

```text
database/schema.sql
```

La seqüència dels `SOURCE`, la classificació dels ajustos incrementals i els canvis històrics ja absorbits es documenten únicament a `database/README.md`.

Per actualitzar una base existent, no s'han d'executar totes les migracions indiscriminadament. Cal identificar l'estat de partida, fer una còpia de seguretat i aplicar només els ajustos posteriors que corresponguin.

### Manteniment d'esquema

Qualsevol comprovació o modificació dinàmica de l'esquema que l'aplicació necessiti executar s'ha de centralitzar en serveis dedicats, com `AdminSchemaMaintenanceService`. Els controladors web no poden contenir instruccions DDL com `CREATE TABLE`, `ALTER TABLE` o modificacions d'esquema equivalents.

Aquest servei és una ubicació temporal i controlada per a manteniment necessari en temps d'execució. Quan un canvi d'esquema sigui estable i segur per a totes les bases, s'ha de consolidar en SQL dins `database/` i documentar-lo seguint `database/README.md`.

---

## Normes generals

- Fer servir InnoDB.
- Fer servir `utf8mb4`.
- Fer servir `utf8mb4_unicode_ci`.
- Fer servir claus primàries.
- Fer servir claus úniques quan calgui evitar duplicats.
- Fer servir PDO.
- Fer servir consultes preparades.
- No concatenar dades d’usuari dins SQL.
- No fer `DROP TABLE` sense confirmació explícita.
- No fer `DROP DATABASE` sense confirmació explícita.
- No fer `DELETE` sense `WHERE`.
- No perdre dades existents.

---

## Usuaris

Taula principal:

```text
users
```

Camps importants:

```text
id
name
surname
email
google_id
password_hash
avatar_url
is_active
last_login_at
created_at
updated_at
```

Criteris:

- `email` ha de ser únic.
- `google_id` pot ser `NULL`.
- `password_hash` pot ser `NULL` si l’usuari només accedeix amb Google.
- No guardar contrasenyes en text pla.

---

## Rols

Taules:

```text
web_roles
user_web_roles
project_roles
project_team_member_roles
```

Funció:

```text
web_roles       → permisos generals d'accés a la web
user_web_roles  → assignació de rols web als usuaris
project_roles   → funcions dels membres dins d'un projecte
project_team_member_roles → assignació d'un o més rols de projecte a cada pertinença d'equip
```

Rols previstos:

```text
student
teacher
coordinator
admin
```

Regla conceptual:

```text
admin       = admin + coordinator + teacher
coordinator = coordinator + teacher
teacher     = teacher
student     = student
```

És preferible assignar diversos rols web explícits a `user_web_roles`. Els rols de projecte no substitueixen els permisos generals de la web.

Un membre d'equip pot tenir més d'un rol de projecte. Per mostrar, filtrar, comptar o importar rols múltiples, s'ha d'utilitzar `project_team_member_roles`. `project_team_members.project_role_id` és només el rol principal de compatibilitat i no s'ha d'usar com a única font funcional.

---

## Classes

Taules:

```text
academic_years
classes
class_members
class_teachers
```

Funció:

```text
academic_years  → cursos acadèmics
classes         → grups classe
class_members   → alumnes dins de classes
class_member_history → historial de canvis de classe
class_teachers  → professorat assignat a classes
```

Classes inicials:

```text
4ESOA
4ESOB
```

Assignació inicial:

```text
Aiman  → 4ESOA
Sílvia → 4ESOB
```

Professorat inicial assignat a les dues classes:

```text
Àlex Martí
Oriol Rovira
Oriol Torrents
```

---

## Projectes

Taules:

```text
projects
project_translations
project_class_assignments
project_assets
project_asset_links
```

Funció:

```text
projects              → dades bàsiques del projecte
project_translations  → títol i descripció per idioma
project_class_assignments → assignació de cada edició a classes
project_assets        → catàleg de logos, softwares i apps
project_asset_links   → relació d’assets amb projectes
```

Projectes inicials:

```text
projecte-rius
mat-penedes
agroparc
projecte-orenetes
liquencity
vespa-velutina
```

Assignacions inicials:

```text
Projecte Rius      → 4ESOA i 4ESOB
MAT Penedès        → 4ESOA
Agroparc           → 4ESOB
Projecte Orenetes  → 4ESOA
Liquencity         → 4ESOB
```

## Documents

Taules:

```text
documents
document_sources
document_fragments
document_visibility_rules
```

Funció:

```text
documents                → documents d'una edició concreta de projecte
document_sources         → fonts d'origen del document
document_fragments       → fragments o blocs reutilitzables
document_visibility_rules → regles de visibilitat per rol, grup o fragment
```

Norma clau:

- `projects` és el catàleg base del projecte;
- `project_academic_years` és la unitat funcional quan una dada depèn del curs concret;
- la clau recomanada és `project_academic_year_id + slug`;
- evita dependre del projecte base per determinar la unitat del document.

Quan usar cada una:

- `projects`: informació estable i compartida del projecte;
- `project_academic_years`: dades que canvien per curs o edició;
- si la consulta necessita saber quin curs és actiu o quin context d'aula hi ha, parteix de `project_academic_years`.

## Avaluació

Taules:

```text
assessment_sources
assessment_import_runs
assessment_records
assessment_import_errors
assessment_phases
assessment_tasks
project_academic_year_phases
project_academic_year_phase_tasks
```

Norma clau:

- `assessment_records` depèn de `assessment_sources`;
- la font i el run han de portar `project_academic_year_id`;
- repetir només `project_id` per filtrar notes és massa ampli si el projecte té diverses edicions;
- el filtratge de notes i imports s'ha de fer per edició, a través de `assessment_sources`;
- les fases i tasques es defineixen una sola vegada i després s'assignen a cada edició de projecte amb les taules pont;
- així no copies la mateixa estructura cada curs.

### Regla de model

- `projects` és el catàleg base del projecte;
- `project_academic_years` és la unitat funcional quan una dada depèn del curs concret;
- si una entitat canvia per edició, no s'ha de resoldre només amb `projects`.

### Estats d'edició

- `project_academic_years.status` controla l'estat global d'una edició i pot ser `pendent`, `actiu`, `realitzat` o `arxivat`;
- `project_class_assignments.status` controla l'estat d'aquesta edició per classe i pot ser `pendent`, `actiu` o `realitzat`;
- l'alumnat veu només edicions de l'any acadèmic actual amb estat d'edició i assignació `actiu` o `realitzat`;
- professorat, coordinació i administració veuen als dashboards edicions de l'any actual amb estat `pendent`, `actiu` o `realitzat`;
- `arxivat` queda fora dels dashboards normals i serveix per conservar històric sense mostrar-lo en el treball diari.

### Equips de projecte

- `project_teams` agrupa els equips per projecte i curs;
- `project_team_members` lliga usuari, equip i classe contextual dins l'edició;
- `project_team_member_roles` lliga cada pertinença amb un o més rols de projecte;
- un alumne pot tenir un equip diferent a cada projecte.

### Quan `project_id` és correcte

- `project_id` és correcte quan la relació apunta al catàleg base del projecte, com `project_translations`, `project_asset_links` o altres relacions compartides per totes les edicions;
- si la dada varia per curs, edició o context d'aula, cal usar `project_academic_year_id` o una taula pont equivalent;
- en documents, imports i avaluació, `project_id` només s'ha de conservar com a dada d'origen o de migració, no com a clau funcional.

---

## Idiomes

Taules:

```text
languages
project_translations
```

Idiomes previstos:

```text
ca
es
en
```

Idioma principal:
```text
ca
```

---

## Assets de projectes

Taules:

```text
project_assets
project_asset_links
```

Funció:

```text
project_assets      → catàleg reutilitzable de logos, softwares, apps i altres recursos
project_asset_links → relació entre projectes i els seus assets visuals o tecnològics
```

Criteris:

- un asset es pot reutilitzar en més d’un projecte;
- un projecte pot tenir diversos assets;
- `logo_path` ha de guardar una ruta relativa dins `public/assets/logos/` o un subdirectori equivalent;
- `website_url` és opcional i permet enllaçar el logo a la seva web oficial;
- `asset_type` pot servir per separar `partner`, `software`, `app` o `tool`;
- `display_order` controla l’ordre de sortida a la portada o a la fitxa del projecte.

Veure també:

- `docs/skills/07-assets-projectes.md`

### Recursos de tasques

La relació entre eines, apps o softwares i una tasca d'avaluació ja es modela amb `assessment_task_resources`. Les bastides i ajudes reutilitzables es cataloguen a `assessment_supports`.

Camps principals de la relació:

```text
assessment_task_id
project_asset_id
support_id
display_order
is_visible
notes
```

Això permet reutilitzar `project_assets` com a catàleg, vincular una bastida a cada recurs i mostrar els recursos exactes que es fan servir a cada tasca sense duplicar dades.

---

## Visibilitat i context

La base ja disposa de `project_sections`, `project_section_roles` i `document_visibility_rules` per controlar seccions i continguts segons el context.

Criteris:

- mantenir una sola font de dades per projecte;
- evitar guardar la mateixa informació duplicada per rol;
- preferir flags o nivells de visibilitat abans que rutes o taules separades per perfil.

Les ampliacions futures de visibilitat han d'estendre aquestes estructures quan sigui possible, en lloc de crear fitxers o còpies de contingut per perfil.

---

## Consultes útils

### Veure taules

```sql
SHOW TABLES;
```

### Veure estructura completa

```sql
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'entorns_de_natura'
ORDER BY TABLE_NAME, ORDINAL_POSITION;
```

### Veure usuaris i rols

```sql
SELECT
    users.id,
    users.name,
    users.surname,
    users.email,
    GROUP_CONCAT(web_roles.name ORDER BY web_roles.name SEPARATOR ', ') AS roles
FROM users
LEFT JOIN user_web_roles ON user_web_roles.user_id = users.id
LEFT JOIN web_roles ON web_roles.id = user_web_roles.role_id
GROUP BY users.id, users.name, users.surname, users.email
ORDER BY users.id;
```

### Veure alumnes per classe

```sql
SELECT
    classes.class_name AS classe,
    users.name,
    users.surname,
    users.email
FROM class_members
JOIN users ON users.id = class_members.user_id
JOIN classes ON classes.id = class_members.class_id
ORDER BY classes.class_name, users.surname, users.name;
```

### Veure professorat per classe

```sql
SELECT
    classes.class_name AS classe,
    users.name,
    users.surname,
    users.email
FROM class_teachers
JOIN users ON users.id = class_teachers.user_id
JOIN classes ON classes.id = class_teachers.class_id
ORDER BY classes.class_name, users.surname, users.name;
```

### Veure projectes per classe

```sql
SELECT 
    classes.class_name AS classe,
    projects.name AS projecte,
    projects.slug,
    project_class_assignments.status
FROM project_class_assignments
JOIN classes ON classes.id = project_class_assignments.class_id
JOIN project_academic_years ON project_academic_years.id = project_class_assignments.project_academic_year_id
JOIN projects ON projects.id = project_academic_years.project_id
ORDER BY classes.class_name, projects.name;
```

### Documents per edició de projecte

```sql
SELECT
    projects.name AS projecte,
    academic_years.name AS curs,
    documents.slug,
    documents.title
FROM documents
JOIN project_academic_years ON project_academic_years.id = documents.project_academic_year_id
JOIN projects ON projects.id = project_academic_years.project_id
JOIN academic_years ON academic_years.id = project_academic_years.academic_year_id
ORDER BY projects.name, academic_years.name, documents.display_order;
```

Norma:

- cada document pertany a una edició concreta del projecte;
- evita basar la lògica de documents en el projecte base;
- usa `project_academic_years` per saber quin curs i quin projecte defineixen el document.

### Veure assets per projecte

```sql
SELECT
    projects.name AS projecte,
    project_assets.name AS asset,
    project_assets.asset_type,
    project_assets.logo_path,
    project_assets.website_url,
    project_asset_links.display_order
FROM project_asset_links
JOIN projects ON projects.id = project_asset_links.project_id
JOIN project_assets ON project_assets.id = project_asset_links.asset_id
WHERE project_asset_links.is_visible = 1
  AND project_assets.is_active = 1
ORDER BY projects.name, project_asset_links.display_order, project_assets.name;
```

---

## Insercions segures

Quan hi pugui haver duplicats, fer servir:

```sql
INSERT IGNORE
```

o:

```sql
ON DUPLICATE KEY UPDATE
```

Això és especialment útil per:

- rols;
- assignacions d’usuaris a rols;
- assignacions d’alumnes a classes;
- assignacions de professorat;
- assignacions de projectes a classes.

---

## Google Workspace

Taules:

```text
google_sources
google_documents
google_document_blocks
google_sheet_rows
google_sync_runs
google_sync_errors
```

Norma clau:

- la unitat funcional és `project_academic_years` quan el contingut és contextual;
- `google_sources` i els resultats sincronitzats han d'anar lligats a `project_academic_year_id`;
- no publicar dades directament des de Google sense validació i sense passar per la BD.
- `google_*` és capa d'origen i sincronització; `documents_*` és capa interna publicable;
- no eliminar `documents`, `document_sources`, `document_fragments` ni `document_visibility_rules` mentre `DocumentService` continuï llegint-les;
- per Docs, el flux recomanat és `google_sources` → `google_documents` → `google_document_blocks` → `documents` / `document_fragments` / `document_visibility_rules`;
- per Sheets, el flux recomanat és `google_sources` → `google_sheet_rows` → `assessment_records` o altres taules finals validades.

---

## Taules previstes per rúbriques i notes

Encara no implementades:

```text
rubrics
rubric_criteria
rubric_levels
assessments
assessment_students
assessment_scores
teacher_observations
```

---

## Criteri principal

La base de dades ha de guardar dades estructurades, segures i relacionades.

No s’ha de copiar literalment l’estructura d’un Google Sheet si no encaixa amb el model de l’aplicació.

Els Google Sheets poden ser font d’importació, però la web ha de treballar amb dades validades dins MySQL/MariaDB.

## Validació de coherència

Després de canvis d'esquema, executa `scripts/check-schema-coherence.php` per detectar:

- camps legacy que encara no s'han eliminat;
- relacions mal situades;
- uniques i índexs que han de seguir existint.
