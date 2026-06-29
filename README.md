# Entorns de Natura

Entorns de Natura és una aplicació web PHP modular pensada per a projectes educatius de 4ESO. El projecte està orientat a convertir-se en una plataforma amb una web pública, un espai per a alumnat, un espai per a professorat, un panell d’administració i una base preparada per a la gestió de projectes educatius i sincronització amb Google.

## Estat actual del projecte

El projecte ja disposa d’una estructura base refactoritzada amb:

- una arquitectura modular amb carpetes per a controladors, models, serveis i helpers;
- una capa de vistes separada amb llenguatges de plantilla PHP;
- una configuració centralitzada amb fitxer `.env`;
- una entrada pública des de `public/index.php`;
- una estructura preparada per a futurs usos amb base de dades, autenticació i multilingüisme;
- un fitxer SQL inicial per crear taules educatives (`database/02_education_tables.sql`).

## Tecnologies utilitzades

- PHP
- HTML, CSS i JavaScript
- MySQL / MariaDB
- PDO
- Apache amb XAMPP
- Arquitectura pròpia modular

## Estructura del projecte

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
│   ├── views/
│   └── lang/
├── storage/
├── .env
├── .env.example
├── composer.json
└── README.md
```

## Requisits

Per executar el projecte localment necessites:

- XAMPP o un servidor local amb Apache i MySQL/MariaDB
- PHP 8 o superior
- Composer (opcional per a futures dependències)

## Instal·lació local

1. Col·loca el projecte dins de la carpeta del servidor local, per exemple:

```text
C:\xampp8.2\htdocs\entorns-de-natura
```

2. Crea el fitxer `.env` a partir de l’exemple:

```bash
copy .env.example .env
```

3. Edita el fitxer `.env` amb la configuració del teu entorn local:

```env
APP_NAME=Entorns de Natura
APP_ENV=local
BASE_URL=/
DB_HOST=localhost
DB_NAME=entorns_de_natura
DB_USER=root
DB_PASS=
```

4. Inicia Apache i MySQL des de XAMPP.

5. Crea la base de dades MySQL/MariaDB si encara no existeix:

```sql
CREATE DATABASE entorns_de_natura;
```

6. Si vols, executa els fitxers SQL de la carpeta `database/` per crear l’estructura inicial.

## Punt d’entrada

La web pública es serveix des de:

```text
public/index.php
```

I es pot accedir localment a:

```text
http://localhost/entorns-de-natura/public/
```

## Fitxers SQL disponibles

Actualment hi ha dos fitxers SQL preparats per a la base de dades:

- `database/schema.sql`: esquema base inicial
- `database/02_education_tables.sql`: taules educatives per a idiomes, cursos, grups, membres, professors, projectes i configuració

## Funcionalitats ja presents

El projecte ja inclou una base sòlida amb:

- pàgina d’inici pública;
- pàgina de projectes;
- pàgina de login;
- estructura preparada per a alumnat, professorat i administració;
- traduccions per a català, castellà i anglès;
- configuració d’entorn amb `.env`;
- estructura modular preparada per escalar.

## Funcionalitats pendents

Aquestes funcionalitats es poden desenvolupar en fases posteriors:

- autenticació real amb usuaris i contrasenyes segures;
- connexió real a MySQL/MariaDB amb PDO completament integrada;
- gestió d’usuaris i rols;
- gestió de grups de classe i membres;
- gestió de projectes educatius;
- rúbriques, notes i dades d’aula;
- sincronització amb Google Docs i Google Sheets;
- millora del disseny i de la UX.

## Recomanacions de desenvolupament

- Mantingues la lògica de negoci dins de `app/`.
- Mantingues les vistes dins de `resources/views/`.
- No escriguis credencials directament al codi.
- Feu servir sempre `.env` per a valors sensibles.
- Mantingues els controladors simples i separa la lògica en serveis.

## Contribució

Aquest projecte està pensat per créixer de manera progressiva. La idea és mantenir el codi ordenat, modular i fàcil d’entendre mentre s’afegeixen noves funcionalitats.

## Autor

Projecte desenvolupat per Oriol Torrents Cabestany i Oriol Rovira Bertran.
