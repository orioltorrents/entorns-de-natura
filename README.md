# Entorns de Natura

**Entorns de Natura** es una aplicacio web educativa per a projectes de 4t d'ESO.

El projecte ha evolucionat des d'una maqueta estatica cap a una aplicacio PHP modular amb web publica, espais per a alumnat i professorat, panell d'administracio i base de dades MySQL/MariaDB.

## Estat actual

El projecte es troba en desenvolupament inicial i ja inclou:

- estructura modular amb `app/`, `config/`, `public/`, `resources/` i `database/`;
- l’esquema actiu del projecte és el definit a `database/02_education_tables.sql`; `database/schema.sql` és una versió antiga i no s’ha d’utilitzar com a referència.
- entrada publica des de `public/index.php`;
- configuracio centralitzada amb `.env`;
- connexio amb MySQL/MariaDB mitjancant PDO;
- taules inicials d'usuaris, rols, classes, projectes i idiomes;
- assignacions inicials de professorat, alumnat, classes i projectes;
- gestio de `class_teachers` des del dashboard d'administracio;
- assets de projectes per logos, softwares i apps associats;
- vistes publiques, d'alumnat, de professorat i d'administracio;
- full d'estils principal a `public/assets/css/styles.css`, amb classes BEM i cache-busting des de `resources/views/layouts/app.php`;
- la maqueta estatica `index.html` i `css/styles.css` es mantenen com a referencia historica o visual;
- base preparada per ampliar amb Google Docs, Google Sheets, rubriques i notes.

## Model d'accés recomanat

El millor enfocament per a les properes fases és un sistema de visibilitat per context dins d'una mateixa aplicacio, no una col·leccio de pàgines separades per perfil.

Proposta de criteri:

- visitant: veu projectes i organització general;
- alumnat: veu tasques assignades, bastides, ajudes i dades d'aula;
- professorat visitant: veu programacions i visió general de tasques, sense dades sensibles;
- professorat que imparteix: veu el contingut complet del projecte;
- administració: veu tot.

Per a la implementacio, el mes net seria reutilitzar una sola vista de projecte i mostrar o ocultar seccions segons el context. Això evita duplicar plantilles i facilita mantenir la coherència.

## UI i CSS

- El CSS actiu de l'aplicacio PHP es `public/assets/css/styles.css`.
- Les vistes noves o refactoritzades han de preferir classes BEM clares i coherents.
- Si una vista ja s'ha migrat del tot, no cal mantenir compatibilitat antiga innecessaria.
- Els canvis visuals petits i segurs son acceptables quan milloren la lectura, la jerarquia o l'usabilitat.
- Si un canvi no apareix al navegador, fer un refresh fort i comprovar el cache-busting del layout.

## Com començar

1. Configura el fitxer `.env`.
2. Engega Apache i MySQL a XAMPP.
3. Obre `http://localhost/entorns-de-natura/public/`.
4. Si no veus els canvis de CSS, fes `Cmd+Shift+R` o reinicia Apache.

## Tecnologies

- PHP
- HTML
- CSS
- JavaScript
- MySQL / MariaDB
- PDO
- Apache amb XAMPP
- Arquitectura propia modular, sense frameworks grans

## Estructura clau

- `public/index.php` es l'entrada de l'aplicacio.
- `resources/views/` conté les plantilles.
- `app/` conté la lògica PHP.
- `config/` conté la configuracio.
- `database/` conté els SQL, migracions i seeds.
- `public/assets/css/styles.css` es el CSS actiu de l'app.
- `index.html` i `css/styles.css` son la maqueta estatica i la referencia visual.
- `database/06_project_assets.sql` afegeix la relacio entre projectes i assets/logos d'entitats, softwares i apps.
- `database/07_task_resources.sql` afegeix bastides, ajudes i recursos lligats a les tasques.
- `database/schema.sql` actua com a fitxer mestre per carregar la base de dades en una reconstruccio neta.
- `docs/skills/07-assets-projectes.md` explica com afegir logos i assets a projectes.

## Entorn local

Ruta local prevista amb XAMPP:

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

## Configuracio

El projecte utilitza un fitxer `.env` per guardar la configuracio local. El fitxer `.env` no s'ha de publicar.

Exemple:

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

## Estructura principal

```text
entorns-de-natura/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Helpers/
├── config/
├── database/
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── resources/
│   ├── lang/
│   └── views/
├── css/
├── supabase/
├── index.html
├── index.php
├── composer.json
└── README.md
```

## Notes de desenvolupament

- Els canvis visuals i de estructura s'han de fer sobre la vista PHP corresponent, no sobre fitxers solts fora de l'arquitectura.
- Quan es refa una vista, es pot retirar compatibilitat antiga si ja no la necessita cap altra part del projecte.
- Si un estil sembla no carregar, revisa `resources/views/layouts/app.php` i el cache-busting del CSS.

## Rols previstos

```text
student
teacher
coordinator
admin
```

Criteri de rols:

```text
admin       -> tambe pot actuar com a coordinator i teacher
coordinator -> tambe pot actuar com a teacher
teacher     -> professorat
student     -> alumnat
```

## Projectes inicials

- Projecte Rius
- MAT Penedes
- Agroparc
- Projecte Orenetes
- Liquencity
- Vespa velutina

## URLs previstes

Web publica:

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

> Nota: en una fase posterior podria afegir-se una entrada de visitant des de la portada per accedir a la part publica i organitzativa, sempre amb contingut limitat.

Zones privades:

```text
/login
/logout
/alumne
/professor
/admin
```

## Google Docs i Google Sheets

El projecte esta pensat per sincronitzar informacio des de Google Workspace:

```text
Google Docs / Google Sheets
        ->
Servei PHP de sincronitzacio
        ->
Base de dades MySQL/MariaDB
        ->
Web publica / alumnat / professorat / administracio
```

## Seguretat

- No publicar el fitxer `.env`.
- No escriure credencials directament dins el codi.
- Protegir les zones internes segons rol.
- No exposar dades personals a la part publica.
- Fer servir `password_hash()` i `password_verify()` si s'utilitzen contrasenyes propies.
- Eliminar o protegir fitxers de prova com `public/test-db.php` quan ja no siguin necessaris.

## Full de ruta

1. Consolidar estructura del projecte.
2. Consolidar connexio a base de dades.
3. Consolidar usuaris, rols, classes i projectes.
4. Crear sistema de rutes estable.
5. Mostrar projectes des de la base de dades.
6. Crear login basic.
7. Crear control d'acces per rols.
8. Crear dashboards inicials.
9. Preparar Google Docs i Google Sheets.
10. Crear rubriques i notes.

## Autoria

Projecte desenvolupat per:

```text
Oriol Torrents Cabestany
Oriol Rovira Bertran
```
