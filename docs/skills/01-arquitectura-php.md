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

## Estat actual

### Implementat

- `public/index.php` és el punt d'entrada real i `index.php` a l'arrel actua com a wrapper;
- hi ha controladors per a web pública, autenticació, alumnat, professorat, administració i importació manual de documents;
- hi ha serveis per a autenticació, projectes, assets, assignacions, seccions, documents, analítica, avaluació i Google Workspace;
- hi ha helpers per a `env`, `route`, `view`, `lang` i `session`;
- hi ha vistes públiques, d'autenticació, d'alumnat, de professorat i d'administració;
- el layout comú ja està centralitzat.

### Encara previst

- un router més formal i extensible;
- decidir si els models actuals es converteixen en una capa real o es mantenen com a placeholders;
- incorporar un sistema d'autoloading o una càrrega de dependències més declarativa.

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

Controladors actuals:

```text
AdminController.php
AuthController.php
DocumentSyncController.php
PublicController.php
StudentController.php
TeacherController.php
```

Responsabilitats:

- rebre la petició;
- validar les dades bàsiques de la petició;
- delegar dades, accions de negoci i manteniment d'esquema als serveis;
- decidir quina vista es carrega;
- no contenir consultes SQL directes;
- no contenir operacions DDL de base de dades, com `CREATE TABLE` o `ALTER TABLE`;
- no contenir HTML extens.

Norma d'or: cap controlador pot contenir consultes SQL directes ni operacions DDL de base de dades. Els controladors han de ser prims i delegar tota la lògica de dades, accions de negoci i manteniment d'esquema als serveis ubicats a `app/Services/`.

---

## Models

Els models actuals són objectes simples o placeholders i no constitueixen la capa principal d'accés a dades del projecte.

Carpeta:

```text
app/Models/
```

Models actuals:

```text
ClassGroup.php
GoogleSource.php
Project.php
User.php
```

Estat real:

- la major part de consultes i transformacions es fan ara des de serveis i alguns controladors;
- `Role.php` i `Language.php` no existeixen com a models actius;
- abans d'afegir més models, cal decidir si aquesta capa representarà entitats, repositoris o només DTOs simples.

---

## Services

Els serveis contenen lògica més complexa.

Carpeta:

```text
app/Services/
```

Serveis actuals:

```text
AnalyticsService.php
AdminActionService.php
AdminAssessmentStructureService.php
AdminClassService.php
AdminDashboardService.php
AdminProjectService.php
AdminSchemaMaintenanceService.php
AdminStudentImportService.php
AdminUserService.php
AssessmentService.php
AssessmentStructureImportService.php
AuthService.php
DocumentImportService.php
DocumentService.php
GoogleSyncService.php
LogService.php
ProjectAssetService.php
ProjectAssignmentService.php
ProjectSectionService.php
ProjectService.php
```

Responsabilitats:

- autenticació;
- comprovació de permisos;
- sincronització amb Google;
- importació de dades;
- registre d’activitat;
- documents, seccions i assets de projecte;
- estructura i registres d'avaluació;
- operacions que impliquen diverses taules.

`RoleService.php` no existeix actualment. Si cal una capa pròpia de rols, s'hauria de crear com a decisió explícita i no assumir-la com a component actiu.

---

## Helpers

Els helpers són funcions petites i reutilitzables.

Carpeta:

```text
app/Helpers/
```

Helpers actuals:

```text
env.php
lang.php
route.php
session.php
view.php
```

Ús recomanat:

- llegir variables d’entorn;
- carregar vistes;
- generar URLs;
- obtenir textos traduïts;
- gestionar inicialització segura de sessió;
- escapar sortides HTML.

`security.php` no existeix actualment com a helper actiu. Les funcions de seguretat s'han d'afegir només si hi ha una necessitat concreta i una ubicació clara.

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

## Deute tècnic actual

Aquests punts descriuen l'estat real del codi i no s'han de presentar com a resolts:

- resolt: `AdminController.php` ja no conté consultes SQL directes ni operacions DDL; les dades del dashboard es preparen a `AdminDashboardService`, el dispatch d'accions POST i l'auditoria admin es deleguen a `AdminActionService`, les accions d'administració de projectes es deleguen a `AdminProjectService`, les accions manuals d'usuaris a `AdminUserService`, les assignacions de professorat a classes a `AdminClassService`, la importació CSV d'alumnes a `AdminStudentImportService`, la gestió d'estructura d'avaluació a `AdminAssessmentStructureService` i el manteniment temporal d'esquema a `AdminSchemaMaintenanceService`;
- pendent: hi ha SQL complex i algunes operacions DDL en serveis d'administració, amb el manteniment d'esquema pendent de consolidar en migracions quan sigui segur;
- la càrrega de dependències es fa manualment des de `public/index.php` i fitxers relacionats;
- no hi ha un autoloading efectiu que carregui classes de manera declarativa;
- els models existents no són encara la capa principal d'accés a dades.

Quan es treballi en aquestes zones, cal preferir extraccions petites cap a serveis existents o nous serveis específics, sense fer una reescriptura completa si no és imprescindible.

---

## Projectes educatius

Els projectes han de venir de la base de dades.

Taules:

```text
projects
project_translations
project_class_assignments
project_academic_years
```

Norma clau:

- `projects` és el catàleg base;
- `project_academic_years` és la unitat real quan la dada depèn del curs o de l'edició;
- documents, imports i notes han de resoldre l'edició abans de llegir o escriure dades sensibles.

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
- No posar consultes SQL ni DDL dins controladors; han de viure en serveis o migracions.
- Escapar sortides HTML amb `htmlspecialchars`.

---

## Criteri principal

El projecte ha de poder créixer sense convertir-se en una col·lecció de fitxers PHP independents.

L’objectiu és construir una aplicació modular, clara i mantenible.
