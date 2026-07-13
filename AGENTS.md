# AGENTS.md — Entorns de Natura

## Context del projecte

**Entorns de Natura** és una aplicació web PHP modular per a projectes educatius de 4ESO.

La plataforma ha de permetre, progressivament:

- web pública;
- espai d’alumnes;
- espai de professorat;
- panell d’administració;
- gestió d’usuaris, rols, classes i projectes;
- futura sincronització amb Google Docs i Google Sheets;
- futura gestió de rúbriques, notes i dades d’aula.

El projecte està en fase inicial, però ja té una base tècnica funcional amb connexió a base de dades.

---

## Entorn de desenvolupament

El projecte s’executa en local amb:

- Windows;
- XAMPP;
- Apache;
- MySQL/MariaDB;
- PHP;
- Visual Studio Code.

Ruta local del projecte:

```text
C:\xampp\htdocs\entorns-de-natura
```

URL local:

```text
http://localhost/entorns-de-natura/public/
```

Base de dades local:

```text
entorns_natura_dev
```

---

## Tecnologies del projecte

Fer servir:

- PHP sense frameworks grans;
- HTML;
- CSS;
- JavaScript;
- MySQL/MariaDB;
- PDO;
- arquitectura pròpia modular.

No fer servir:

- WordPress;
- Drupal;
- Joomla;
- Laravel;
- Symfony;
- frameworks grans;
- CMS externs;
- credencials escrites directament al codi.

---

## Estructura general del projecte

L’estructura principal del projecte és:

```text
entorns-de-natura/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Helpers/
│
├── config/
│   ├── app.php
│   └── database.php
│
├── database/
│   ├── migrations/
│   ├── seeds/
│   ├── schema.sql
│   └── 02_education_tables.sql
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       ├── js/
│       ├── logos/
│       └── img/
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── public/
│   │   ├── auth/
│   │   ├── students/
│   │   ├── teachers/
│   │   └── admin/
│   │
│   └── lang/
│       ├── ca.php
│       ├── es.php
│       └── en.php
│
├── storage/
│   ├── logs/
│   ├── cache/
│   └── synced/
│
├── docs/
│   └── skills/
│
├── .env
├── .env.example
├── .gitignore
├── composer.json
├── README.md
└── AGENTS.md
```

---

## Normes d’estructura

- `public/index.php` és el punt d’entrada de l’aplicació.
- Els assets públics han d’estar dins `public/assets/`.
- Els fitxers JavaScript i CSS utilitzats per la web pública han de viure a `public/assets/`; si un script s’afegeix a `assets/` fora de `public/`, no es carregarà correctament des del navegador.
- Les vistes han d’estar dins `resources/views/`.
- La lògica PHP ha d’estar dins `app/`.
- La configuració ha d’estar dins `config/`.
- Els SQL, migracions i seeds han d’estar dins `database/`.
- Els logs, cache i dades sincronitzades han d’estar dins `storage/`.
- La documentació i els skills han d’estar dins `docs/`.
- No crear pàgines PHP soltes per cada projecte.
- No barrejar HTML, SQL i lògica de negoci dins un mateix fitxer quan es pugui evitar.

---

## Configuració d’entorn

El projecte utilitza un fitxer `.env`.

Exemple de configuració local:

```env
APP_NAME="Entorns de Natura"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/entorns-de-natura/public

DB_HOST=localhost
DB_NAME=entorns_natura_dev
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Normes:

- No publicar mai `.env`.
- No escriure credencials directament al codi.
- Crear i mantenir `.env.example` sense credencials sensibles.
- La configuració sensible ha d’estar fora del repositori.

---

## Base de dades actual

La base de dades local actual és:

```text
entorns_natura_dev
```

Taules existents:

```text
academic_years
classes
class_members
class_teachers
languages
project_assets
project_asset_links
projects
project_translations
project_groups
assessment_sources
assessment_import_runs
assessment_records
assessment_import_errors
assessment_phases
assessment_tasks
assessment_supports
assessment_task_resources
roles
settings
site_visits
student_profiles
users
user_roles
```

Ordre recomanat de càrrega per reconstruir la base de dades:

```text
database/schema.sql
  -> 02_education_tables.sql
  -> 03_assessment_tables.sql
  -> 04_assessment_structure_tables.sql
  -> 06_project_assets.sql
  -> 07_task_resources.sql
  -> 08_document_tables.sql
```

La migració `05_project_display_order.sql` es manté com a canvi no destructiu per a bases ja creades; en una reconstrucció neta no és necessària perquè `display_order` ja ve definit a la base.

`database/schema.sql` és el punt de partida mestre de reconstrucció. Les peces `02`, `03`, `04`, `06`, `07` i `08` formen l'esquema actual.

Si la base ja existia abans de la capa de documents, cal aplicar també `database/09_document_tables_fix.sql` com a ajust no destructiu.

Quan es necessiti relacionar eines, apps o recursos amb tasques, la solució recomanada és una taula de relació separada, `assessment_task_resources`, reutilitzant `project_assets` com a catàleg i `assessment_supports` per a bastides o ajudes associades.

---

## Connexió a base de dades

La connexió es fa amb PDO des de:

```text
config/database.php
```

Normes:

- Fer servir PDO.
- Fer servir consultes preparades.
- No concatenar dades d’usuari directament en consultes SQL.
- Fer servir `utf8mb4`.
- Fer servir `utf8mb4_unicode_ci`.
- Fer servir InnoDB.
- Gestionar errors amb excepcions.
- No mostrar errors sensibles en producció.

---

## Usuaris i rols

Taules relacionades:

```text
users
roles
user_roles
```

Rols previstos:

```text
student
teacher
coordinator
admin
```

Regla conceptual de permisos:

```text
admin       → també pot actuar com a coordinator i teacher
coordinator → també pot actuar com a teacher
teacher     → professor
student     → alumne
```

És preferible assignar diversos rols explícits a la taula `user_roles`.

Exemples:

```text
admin       = admin + coordinator + teacher
coordinator = coordinator + teacher
teacher     = teacher
student     = student
```

Usuaris inicials previstos o creats:

```text
Oriol Torrents → admin + coordinator + teacher
Oriol Rovira   → coordinator + teacher
Àlex Martí     → teacher
Aiman          → student
Sílvia         → student
```

---

## Classes i professorat

Taules relacionades:

```text
academic_years
classes
class_members
class_teachers
```

Classes inicials:

```text
4ESOA
4ESOB
```

Assignacions inicials d’alumnes:

```text
Aiman  → 4ESOA
Sílvia → 4ESOB
```

Professorat assignat a 4ESOA i 4ESOB:

```text
Àlex Martí
Oriol Rovira
Oriol Torrents
```

`class_members` relaciona alumnes amb classes.

`class_teachers` relaciona professorat amb classes.

---

## Projectes educatius

Taules relacionades:

```text
projects
project_translations
project_groups
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

Noms visibles:

```text
Projecte Rius
MAT Penedès
Agroparc
Projecte Orenetes
Liquencity
Vespa velutina
```

Assignacions inicials de projectes:

```text
Projecte Rius      → 4ESOA i 4ESOB
MAT Penedès        → 4ESOA
Agroparc           → 4ESOB
Projecte Orenetes  → 4ESOA
Liquencity         → 4ESOB
```

La taula `project_groups` relaciona projectes amb classes.

---

## Norma sobre projectes

No crear fitxers PHP separats per projecte, com:

```text
agroparc.php
liquencity.php
projecte-rius.php
projecte-orenetes.php
```

Els projectes han de venir de la base de dades i mostrar-se amb una plantilla genèrica.

Vista recomanada:

```text
resources/views/public/project-detail.php
```

Ruta recomanada:

```text
/ca/projectes/{slug}
```

Exemples:

```text
/ca/projectes/projecte-rius
/ca/projectes/agroparc
/ca/projectes/liquencity
```

---

## Idiomes

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

Taules relacionades:

```text
languages
project_translations
```

Criteris:

- La interfície ha d’estar preparada per traduccions.
- Els continguts llargs poden venir més endavant de base de dades o Google Docs.
- Les rutes públiques poden incloure idioma.

---

## Rutes públiques previstes

```text
/
/ca
/ca/projectes
/ca/projectes/projecte-rius
/ca/projectes/mat-penedes
/ca/projectes/agroparc
/ca/projectes/projecte-orenetes
/ca/projectes/liquencity
/ca/projectes/vespa-velutina
```

---

## Rutes privades previstes

```text
/login
/logout

/alumne
/alumne/projectes
/alumne/materials
/alumne/rubriques

/professor
/professor/grups
/professor/alumnes
/professor/projectes
/professor/rubriques
/professor/notes

/admin
/admin/usuaris
/admin/projectes
/admin/google-sources
/admin/sincronitzacio
/admin/logs
/admin/configuracio
```

---

## Normes de codi PHP

- Fer servir `declare(strict_types=1);` en fitxers PHP nous.
- Fer servir PDO per accedir a la base de dades.
- Fer servir consultes preparades.
- No concatenar dades d’usuari directament en SQL.
- Escapar sortides HTML amb `htmlspecialchars`.
- Separar controladors, models, serveis i vistes.
- Evitar fitxers massa grans.
- Evitar repetir codi.
- No barrejar lògica de negoci dins les vistes.
- Mantenir els controladors simples.
- Posar la lògica complexa dins serveis.
- Fer que el codi sigui llegible abans que excessivament abstracte.

---

## Normes de seguretat

- No publicar `.env`.
- No exposar dades personals a la part pública.
- No deixar fitxers de prova accessibles en producció.
- No guardar contrasenyes en text pla.
- Fer servir `password_hash()` i `password_verify()` quan hi hagi contrasenyes pròpies.
- Els usuaris que només accedeixin amb Google poden tenir `password_hash = NULL`.
- No mostrar errors sensibles en producció.
- Validar dades d’entrada.
- Escapar sortides HTML.
- Protegir les zones privades per sessió i rol.
- No posar claus de Google al JavaScript.
- No posar credencials dins fitxers versionats.

Fitxer temporal a eliminar o protegir quan ja no calgui:

```text
public/test-db.php
```

---

## Autenticació prevista

Inicialment es pot crear login bàsic.

Més endavant es preveu login amb Google.

Camps importants a `users`:

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
- `google_id` pot ser `NULL` fins que es faci login amb Google.
- `password_hash` pot ser `NULL` si l’usuari només accedeix amb Google.
- El control de permisos s’ha de fer amb `user_roles`.

---

## Control d’accés previst

- `/alumne` requereix rol `student`.
- `/professor` requereix rol `teacher`.
- `/admin` requereix rol `admin`.

Com que els admins i coordinadors poden tenir diversos rols explícits, no cal fer excepcions complexes.

Exemple:

```text
Oriol Torrents té admin + coordinator + teacher
```

Per tant, pot accedir a:

```text
/admin
/professor
```

---

## Google Docs i Google Sheets

El projecte està pensat per sincronitzar informació des de Google Workspace.

Flux previst:

```text
Google Docs / Google Sheets
        ↓
Servei PHP de sincronització
        ↓
Base de dades MySQL/MariaDB
        ↓
Web pública / alumnat / professorat / administració
```

Taules previstes:

```text
google_sources
synced_documents
synced_sheet_rows
google_sync_runs
google_sync_errors
```

Funció prevista:

- `google_sources`: registrar documents o fulls de Google vinculats a projectes.
- `synced_documents`: guardar contingut processat de Google Docs.
- `synced_sheet_rows`: guardar files importades de Google Sheets.
- `google_sync_runs`: registrar execucions de sincronització.
- `google_sync_errors`: registrar errors de sincronització.

Normes:

- No posar credencials de Google al JavaScript.
- No publicar claus de Google.
- Validar dades abans d’importar-les.
- Guardar logs de sincronització.
- No publicar dades sensibles d’alumnes directament des d’un Google Sheet.
- Les dades privades o avaluables han de passar per la base de dades.

---

## Rúbriques i notes

Encara no estan implementades.

Taules previstes més endavant:

```text
rubrics
rubric_criteria
rubric_levels
assessments
assessment_students
assessment_scores
teacher_observations
```

Criteris:

- Les notes i observacions són dades sensibles.
- No s’han de mostrar mai públicament.
- L’accés ha d’estar restringit per rol.
- Les dades d’avaluació han d’estar controlades a la base de dades.
- Els Google Sheets poden servir com a font d’importació, però no com a publicació directa de notes.

---

## Manera de treballar

Abans de modificar fitxers importants:

1. Revisar l’estructura actual.
2. Explicar quins fitxers es canviaran.
3. Fer canvis petits i coherents.
4. No barrejar moltes funcionalitats en una sola intervenció.
5. Prioritzar estabilitat i claredat.
6. Actualitzar el README quan hi hagi canvis importants.
7. No fer canvis destructius sense confirmació explícita.

## Model recomanat de visibilitat

Quan es treballi en projectes, rutes o vistes que mostrin contingut educatiu, el criteri recomanat és:

- una sola vista per projecte;
- seccions condicionades pel context d'accés;
- no duplicar plantilles per cada perfil;
- no mostrar dades sensibles a visitants ni alumnat si no toca;
- reservar programacions completes, deadlines i informació interna per al professorat que imparteix;
- deixar la porta oberta a un mode de visitant amb informació pública i organitzativa.

Si un canvi implica separar massa la informació en fitxers o rutes diferents, cal aturar-se i valorar una solució més centralitzada.

---

## Canvis destructius

No fer sense confirmació explícita:

```text
DROP DATABASE
DROP TABLE
TRUNCATE TABLE
DELETE sense WHERE
reescriptura completa de fitxers importants
eliminació de carpetes
canvis massius en l’estructura
```

Quan calgui fer un canvi destructiu, primer cal explicar:

```text
què s’eliminarà
per què cal eliminar-ho
com es pot recuperar
quina alternativa menys destructiva hi ha
```

---

## Ordre recomanat de desenvolupament

Fase actual:

```text
1. Consolidar estructura del projecte.
2. Consolidar connexió a base de dades.
3. Consolidar usuaris, rols, classes i projectes.
```

Fases següents:

```text
4. Crear sistema de rutes estable.
5. Crear layout general.
6. Mostrar projectes des de la base de dades.
7. Crear login bàsic.
8. Crear control d’accés per rols.
9. Crear dashboards inicials.
10. Preparar Google Docs i Google Sheets.
11. Crear rúbriques.
12. Crear notes i dades d’aula.
```

---

## Skills disponibles

Consultar els fitxers de `docs/skills/` abans de fer tasques específiques.

Skills previstos:

```text
docs/skills/01-arquitectura-php.md
docs/skills/02-base-de-dades.md
docs/skills/03-rutes-i-vistes.md
docs/skills/04-auth-rols-i-seguretat.md
docs/skills/05-google-docs-sheets.md
docs/skills/06-css-i-ui.md
```

---

## Regla principal per a Codex

Abans de fer canvis, Codex ha de tenir present:

```text
Aquest projecte és una aplicació PHP pròpia, modular, sense frameworks grans, amb dades educatives sensibles i preparada per créixer de manera ordenada.
```

Prioritats:

```text
1. Seguretat.
2. Claredat.
3. Estructura.
4. Dades ben modelades.
5. Reutilització.
6. Escalabilitat.
7. Disseny i experiència d’usuari.
```

---

## Resum del criteri general

No construir una col·lecció de pàgines PHP independents.

Construir una aplicació web modular on:

```text
public/index.php rep les peticions
app/ conté la lògica
resources/views/ conté les plantilles
config/ conté la configuració
database/ conté SQL i migracions
storage/ conté logs i cache
MySQL/MariaDB guarda les dades
Google Docs/Sheets poden actuar com a font sincronitzable
```

El codi ha de permetre que Entorns de Natura evolucioni cap a una plataforma educativa completa.
