# Entorns de Natura

**Entorns de Natura** és una aplicació web PHP modular pensada per a projectes educatius de 4ESO. El projecte està orientat a convertir-se en una plataforma amb una web pública, un espai per a alumnat, un espai per a professorat, un panell d’administració i una base preparada per a la gestió de projectes educatius, rúbriques, dades d’aula i sincronització amb Google Docs i Google Sheets.

## Estat actual del projecte

El projecte es troba en fase inicial de desenvolupament, però ja disposa d’una base tècnica funcional:

- estructura modular del projecte;
- entrada pública des de `public/index.php`;
- configuració centralitzada amb `.env`;
- connexió funcional amb MySQL/MariaDB mitjançant PDO;
- base de dades local creada;
- taules inicials d’usuaris, rols, classes, projectes i idiomes;
- primers usuaris, rols, classes i assignacions creades;
- estructura preparada per ampliar amb Google Docs, Google Sheets, rúbriques i notes.

## Tecnologies utilitzades

- PHP
- HTML
- CSS
- JavaScript
- MySQL / MariaDB
- PDO
- Apache amb XAMPP
- Visual Studio Code
- Arquitectura pròpia modular, sense frameworks grans

## Entorn local actual

El projecte s’està desenvolupant en local amb XAMPP.

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

## Estructura del projecte

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
│   ├── test-db.php
│   └── assets/
│       ├── css/
│       ├── js/
│       ├── img/
│       ├── icons/
│       └── uploads/
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
├── .env
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

> Nota: `public/test-db.php` és un fitxer temporal de prova de connexió amb la base de dades. Quan la connexió ja estigui validada, es recomana eliminar-lo o protegir-lo.

## Configuració de l'entorn

El projecte utilitza un fitxer `.env` per guardar la configuració local.

Exemple de configuració actual:

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

En l’entorn local amb XAMPP, l’usuari habitual de MySQL/MariaDB és:

```text
root
```

i la contrasenya acostuma a estar buida.

## Connexió a la base de dades

La connexió amb la base de dades es fa amb PDO des de:

```text
config/database.php
```

La connexió ja ha estat provada correctament amb la base de dades:

```text
entorns_natura_dev
```

Per provar-la temporalment s’ha utilitzat:

```text
public/test-db.php
```

Aquest fitxer fa una consulta bàsica a la taula `users`.

## Base de dades

La base de dades local actual és:

```text
entorns_natura_dev
```

Les taules creades actualment són:

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

## Estructura actual de dades

### Usuaris i rols

Taules relacionades:

```text
users
roles
user_roles
```

Aquest bloc permet gestionar:

- alumnes;
- professorat;
- coordinadors;
- administradors;
- múltiples rols per un mateix usuari.

Rols previstos:

```text
student
teacher
coordinator
admin
```

Criteri de rols:

```text
admin       → també pot actuar com a coordinator i teacher
coordinator → també pot actuar com a teacher
teacher     → professor
student     → alumne
```

### Cursos i classes

Taules relacionades:

```text
academic_years
classes
class_members
class_teachers
```

Aquest bloc permet gestionar:

- curs acadèmic;
- grups classe;
- alumnes dins de cada grup;
- professorat assignat a cada grup.

Classes inicials:

```text
4ESOA
4ESOB
```

Assignació inicial d’alumnes:

```text
Aiman  → 4ESOA
Sílvia → 4ESOB
```

Assignació inicial de professorat:

```text
Àlex Martí     → 4ESOA i 4ESOB
Oriol Rovira   → 4ESOA i 4ESOB
Oriol Torrents → 4ESOA i 4ESOB
```

### Projectes educatius

Taules relacionades:

```text
projects
project_translations
project_groups
```

Projectes inicials:

```text
Projecte Rius
MAT Penedès
Agroparc
Projecte Orenetes
Liquencity
Vespa velutina
```

Assignació inicial de projectes:

```text
Projecte Rius      → 4ESOA i 4ESOB
MAT Penedès        → 4ESOA
Agroparc           → 4ESOB
Projecte Orenetes  → 4ESOA
Liquencity         → 4ESOB
```

La taula `project_groups` relaciona projectes amb classes.

## URLs previstes

La web pública hauria de preparar rutes com:

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

Zones privades previstes:

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

## Funcionalitats ja iniciades

El projecte ja inclou o té preparat:

- estructura modular de carpetes;
- fitxer `.env`;
- connexió PDO amb la base de dades;
- base de dades local funcional;
- usuaris inicials;
- rols inicials;
- assignació d’usuaris a rols;
- classes inicials;
- assignació d’alumnes a classes;
- assignació de professorat a classes;
- projectes inicials;
- assignació de projectes a classes;
- idiomes preparats;
- estructura per a vistes públiques, alumnat, professorat i administració.

## Funcionalitats pendents

Funcionalitats pendents de desenvolupar:

- sistema de login real;
- control de sessions;
- control d’accés segons rol;
- dashboard d’alumne;
- dashboard de professorat;
- dashboard d’administració;
- gestió visual d’usuaris;
- gestió visual de classes;
- gestió visual de projectes;
- integració amb Google Login;
- integració amb Google Docs;
- integració amb Google Sheets;
- sistema de sincronització de dades;
- rúbriques;
- notes;
- observacions d’aula;
- dades científiques dels projectes;
- millora del disseny i de l’experiència d’usuari.

## Google Docs i Google Sheets

El projecte està pensat perquè més endavant pugui sincronitzar informació des de Google Workspace.

La idea prevista és:

```text
Google Docs / Google Sheets
        ↓
Servei PHP de sincronització
        ↓
Base de dades MySQL/MariaDB
        ↓
Web pública / alumnat / professorat / administració
```

Taules que caldrà crear més endavant:

```text
google_sources
synced_documents
synced_sheet_rows
google_sync_runs
google_sync_errors
```

Aquestes taules serviran per:

- registrar documents de Google vinculats a projectes;
- guardar contingut sincronitzat;
- importar files de Google Sheets;
- registrar execucions de sincronització;
- registrar errors de sincronització.

## Recomanacions de desenvolupament

- Mantingues la lògica de negoci dins de `app/`.
- Mantingues les vistes dins de `resources/views/`.
- Mantingues la configuració dins de `config/`.
- Mantingues els fitxers públics dins de `public/`.
- No escriguis credencials directament dins el codi.
- Fes servir sempre `.env` per a valors sensibles.
- Fes servir PDO per accedir a la base de dades.
- Evita crear pàgines PHP soltes per a cada projecte.
- Els projectes han de venir de la base de dades i mostrar-se amb una plantilla genèrica.
- Mantingues els controladors simples.
- Separa la lògica complexa en serveis.

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
11. Crear rúbriques i notes.
```

## Notes sobre seguretat

Aquest projecte gestionarà dades d’alumnat i professorat. Per tant, cal tenir en compte:

- no exposar dades personals a la part pública;
- protegir l’accés a zones internes;
- no guardar contrasenyes en text pla;
- fer servir `password_hash()` i `password_verify()` si s’utilitzen contrasenyes pròpies;
- deixar `password_hash` com a `NULL` en usuaris que accedeixin només amb Google;
- no publicar el fitxer `.env`;
- eliminar fitxers de prova com `public/test-db.php` quan ja no siguin necessaris.

## Autor

Projecte desenvolupat per:

```text
Oriol Torrents Cabestany
Oriol Rovira Bertran
```

## Estat

Projecte en desenvolupament inicial.