# Entorns de Natura

Entorns de Natura és una aplicació web PHP modular pensada per a projectes educatius de 4ESO, amb una estructura preparada per a una web pública, un espai per a alumnat, un espai per a professorat, una zona d’administració i futur suport per a sincronització amb Google Docs i Google Sheets.

## Objectiu del projecte

Aquest projecte pretén servir com a base per a:

- una web pública amb informació dels projectes educatius;
- un espai d’alumnat amb accés personalitzat;
- un espai de professorat amb eines de seguiment;
- un panell d’administració per gestionar continguts i usuaris;
- un futur sistema multilingüe en català, castellà i anglès;
- una arquitectura preparada per créixer sense dependre de frameworks grans.

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

Per executar aquest projecte necessites:

- XAMPP o un servidor local amb Apache i MySQL/MariaDB
- PHP 8 o superior
- Composer (opcional per a futures dependències)

## Instal·lació local

1. Clona o copia el projecte a la carpeta del servidor local, per exemple:

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

5. Crea la base de dades MySQL/MariaDB manualment, per exemple:

```sql
CREATE DATABASE entorns_de_natura;
```

6. Executa l’esquema SQL que hi ha a `database/schema.sql` si vols començar a treballar amb una estructura inicial.

## Punt d’entrada

La web pública es serveix des de:

```text
public/index.php
```

Per accedir localment:

```text
http://localhost/entorns-de-natura/public/
```

## Funcionalitats actuals

Actualment el projecte ja inclou una base modular amb:

- pàgina d’inici pública;
- pàgina de projectes;
- pàgina de login;
- estructura preparada per a alumnat, professorat i administració;
- fitxers de traducció per a català, castellà i anglès;
- configuració d’entorn amb `.env`;
- estructura de carpetes preparada per escalar.

## Funcionalitats pendents

Les següents parts es poden desenvolupar en fases posteriors:

- autenticació real amb usuaris i contrasenyes protegides;
- connexió real a MySQL/MariaDB amb PDO;
- gestió d’usuaris i rols;
- gestió de grups de classe;
- gestió de projectes educatius;
- rúbriques, notes i dades d’aula;
- sincronització amb Google Docs i Google Sheets;
- millora del disseny i la UX.

## Base de dades

La base de dades encara no està creada. Quan la creïs, pots fer servir el fitxer:

```text
database/schema.sql
```

com a punt de partida per a l’esquema inicial.

## Recomanacions de desenvolupament

- Mantingueu la lògica de negoci dins de `app/`.
- Mantingueu les vistes dins de `resources/views/`.
- No escriviu credencials directament al codi.
- Feu servir sempre `.env` per a valors sensibles.
- Mantenir els controladors senzills i la lògica en serveis.

## Contribució

Aquest projecte està pensat per créixer de manera progressiva. Si vols col·laborar, el millor és seguir l’estructura actual i mantenir el codi ordenat, modular i fàcil d’entendre.

## Autor

Projecte desenvolupat per a Entorns de Natura.
