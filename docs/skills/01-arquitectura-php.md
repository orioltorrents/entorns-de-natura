# Skill 01 — Arquitectura PHP

## Objectiu

Mantenir el projecte **Entorns de Natura** ordenat, modular i fàcil de mantenir.

El projecte és una aplicació PHP pròpia, sense frameworks grans, preparada per tenir:

- web pública;
- espai d’alumnes;
- espai de professorat;
- panell d’administració;
- base de dades MySQL/MariaDB;
- futura sincronització amb Google Docs i Google Sheets;
- futura gestió de rúbriques i notes.

---

## Estructura principal

L’estructura base del projecte és:

```text
entorns-de-natura/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   └── Helpers/
│
├── config/
├── database/
├── public/
├── resources/
├── storage/
└── docs/
```

---

## Normes generals

- El punt d’entrada és `public/index.php`.
- La lògica de l’aplicació va dins `app/`.
- Les vistes van dins `resources/views/`.
- La configuració va dins `config/`.
- Els fitxers públics van dins `public/`.
- Els assets van dins `public/assets/`.
- Els SQL, seeds i migracions van dins `database/`.
- Els logs, cache i dades sincronitzades van dins `storage/`.
- La documentació va dins `docs/`.

---

## No fer

No crear pàgines PHP soltes per cada projecte, com:

```text
agroparc.php
liquencity.php
projecte-rius.php
projecte-orenetes.php
```

No barrejar dins un mateix fitxer:

```text
HTML + SQL + lògica de negoci + control d'accés
```

No escriure credencials dins el codi.

No fer servir CMS ni frameworks grans.

---

## Controladors

Els controladors han de coordinar la petició i retornar una vista.

Carpeta:

```text
app/Controllers/
```

Controladors previstos:

```text
PublicController.php
AuthController.php
StudentController.php
TeacherController.php
AdminController.php
```

Responsabilitats:

- rebre la petició;
- obtenir dades dels models o serveis;
- decidir quina vista es carrega;
- no contenir SQL complex;
- no contenir HTML extens.

---

## Models

Els models representen dades de la base de dades.

Carpeta:

```text
app/Models/
```

Models previstos:

```text
User.php
Project.php
ClassGroup.php
Role.php
Language.php
GoogleSource.php
```

Responsabilitats:

- llegir dades;
- crear dades;
- actualitzar dades;
- encapsular consultes relacionades amb una entitat.

---

## Services

Els serveis contenen lògica més complexa.

Carpeta:

```text
app/Services/
```

Serveis previstos:

```text
AuthService.php
GoogleSyncService.php
LogService.php
ProjectService.php
RoleService.php
```

Responsabilitats:

- autenticació;
- comprovació de permisos;
- sincronització amb Google;
- importació de dades;
- registre d’activitat;
- operacions que impliquen diverses taules.

---

## Helpers

Els helpers són funcions petites i reutilitzables.

Carpeta:

```text
app/Helpers/
```

Helpers previstos:

```text
env.php
view.php
route.php
lang.php
security.php
```

Ús recomanat:

- llegir variables d’entorn;
- carregar vistes;
- generar URLs;
- obtenir textos traduïts;
- escapar sortides HTML.

---

## Vistes

Les vistes han d’estar a:

```text
resources/views/
```

Estructura recomanada:

```text
resources/views/
├── layouts/
├── public/
├── auth/
├── students/
├── teachers/
└── admin/
```

Les vistes no haurien de contenir SQL.

Les vistes han de mostrar dades ja preparades pel controlador.

---

## Layout

El layout general ha d’evitar repetir estructura HTML a totes les pàgines.

Fitxers recomanats:

```text
resources/views/layouts/app.php
resources/views/layouts/header.php
resources/views/layouts/footer.php
```

---

## Projectes educatius

Els projectes han de venir de la base de dades.

Taules:

```text
projects
project_translations
project_groups
```

Vista genèrica:

```text
resources/views/public/project-detail.php
```

Ruta prevista:

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

## Normes de codi PHP

- Fer servir `declare(strict_types=1);` en fitxers PHP nous.
- Fer servir noms clars.
- Evitar fitxers massa grans.
- Evitar duplicació.
- Separar responsabilitats.
- Fer servir PDO per a la base de dades.
- Fer servir consultes preparades.
- Escapar sortides HTML amb `htmlspecialchars`.

---

## Criteri principal

El projecte ha de poder créixer sense convertir-se en una col·lecció de fitxers PHP independents.

L’objectiu és construir una aplicació modular, clara i mantenible.