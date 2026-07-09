# Entorns de Natura

**Entorns de Natura** es una aplicacio web educativa per a projectes de 4t d'ESO. El projecte ha evolucionat des d'una primera maqueta estatica cap a una aplicacio PHP modular amb web publica, espais per a alumnat i professorat, panell d'administracio i base de dades MySQL/MariaDB.

## Estat actual

El projecte es troba en desenvolupament inicial i ja inclou:

- estructura modular amb `app/`, `config/`, `public/`, `resources/` i `database/`;
- l’esquema actiu del projecte és el definit a `database/02_education_tables.sql`; `database/schema.sql` és una versió antiga i no s’ha d’utilitzar com a referència.
- entrada publica des de `public/index.php`;
- configuracio centralitzada amb `.env`;
- connexio amb MySQL/MariaDB mitjancant PDO;
- taules inicials d'usuaris, rols, classes, projectes i idiomes;
- assignacions inicials de professorat, alumnat, classes i projectes;
- vistes publiques, d'alumnat, de professorat i d'administracio;
- base preparada per ampliar amb Google Docs, Google Sheets, rubriques i notes.

També es conserven els fitxers de la maqueta estàtica del projecte, com `index.html` i `css/styles.css`, i les migracions de `supabase/`, com a referencia historica o punt de recuperacio.

## Tecnologies

- PHP
- HTML
- CSS
- JavaScript
- MySQL / MariaDB
- PDO
- Apache amb XAMPP
- Arquitectura propia modular, sense frameworks grans

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
