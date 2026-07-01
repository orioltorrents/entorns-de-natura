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
- idiomes;
- assignacions de projectes;
- futura sincronització amb Google;
- futures rúbriques i notes.

---

## Base de dades local

Nom de la base de dades local:

```text
entorns_natura_dev
```

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
DB_NAME=entorns_natura_dev
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

---

## Taules actuals

La base de dades actual té aquestes taules:

```text
academic_years
classes
class_members
class_teachers
languages
projects
project_translations
project_groups
roles
settings
users
user_roles
```

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

Regla conceptual:

```text
admin       = admin + coordinator + teacher
coordinator = coordinator + teacher
teacher     = teacher
student     = student
```

És preferible assignar diversos rols explícits a `user_roles`.

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
project_groups
```

Funció:

```text
projects              → dades bàsiques del projecte
project_translations  → títol i descripció per idioma
project_groups        → assignació de projectes a classes
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
WHERE TABLE_SCHEMA = 'entorns_natura_dev'
ORDER BY TABLE_NAME, ORDINAL_POSITION;
```

### Veure usuaris i rols

```sql
SELECT 
    users.id,
    users.name,
    users.surname,
    users.email,
    GROUP_CONCAT(roles.name ORDER BY roles.name SEPARATOR ', ') AS roles
FROM users
LEFT JOIN user_roles ON user_roles.user_id = users.id
LEFT JOIN roles ON roles.id = user_roles.role_id
GROUP BY users.id, users.name, users.surname, users.email
ORDER BY users.id;
```

### Veure alumnes per classe

```sql
SELECT 
    classes.name AS classe,
    users.name,
    users.surname,
    users.email
FROM class_members
JOIN users ON users.id = class_members.user_id
JOIN classes ON classes.id = class_members.class_id
ORDER BY classes.name, users.surname, users.name;
```

### Veure professorat per classe

```sql
SELECT 
    classes.name AS classe,
    users.name,
    users.surname,
    users.email
FROM class_teachers
JOIN users ON users.id = class_teachers.user_id
JOIN classes ON classes.id = class_teachers.class_id
ORDER BY classes.name, users.surname, users.name;
```

### Veure projectes per classe

```sql
SELECT 
    classes.name AS classe,
    projects.name AS projecte,
    projects.slug,
    project_groups.status
FROM project_groups
JOIN projects ON projects.id = project_groups.project_id
JOIN classes ON classes.id = project_groups.class_id
ORDER BY classes.name, projects.name;
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

## Taules previstes per Google

Encara no implementades:

```text
google_sources
synced_documents
synced_sheet_rows
google_sync_runs
google_sync_errors
```

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