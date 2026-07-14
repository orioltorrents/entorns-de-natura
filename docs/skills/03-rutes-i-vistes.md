# Skill 03 — Rutes i vistes

## Objectiu

Organitzar les rutes i les vistes del projecte **Entorns de Natura** sense crear fitxers PHP solts per cada pàgina.

El projecte ha de funcionar com una aplicació PHP modular.

## Estat actual

### Implementat

- entrada única a `public/index.php`;
- rutes públiques per portada, llistat de projectes, detall de projecte i login;
- rutes privades per a alumnat, professorat i administració;
- vistes ja creades a `resources/views/public`, `auth`, `students`, `teachers` i `admin`;
- layout compartit a `resources/views/layouts/app.php`.

### Encara previst

- un router més net i declaratiu;
- ampliar l'idioma més enllà de `ca` quan toqui;
- afinar la vista de projecte per més contextos d'accés sense duplicar fitxers.

## Criteri d'edició

Quan una vista mostri documents, notes o materials que depenen del curs concret del projecte, la unitat funcional ha de ser `project_academic_years`.

- `projects` identifica el projecte base;
- `project_academic_years` identifica l'edició activa;
- les vistes públiques han de resoldre l'edició abans de pintar dades sensibles o contextuals.

---

## Punt d’entrada

El punt d’entrada és:

```text
public/index.php
```

Totes les peticions principals han de passar per aquest fitxer.

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

## Rutes actuals

```text
/
/ca
/projectes
/ca/projectes
/es/projectes
/en/projectes
/ca/projectes/{slug}
/login
/logout
/dashboard
/alumne
/professor
/admin
```

La ruta de detall de projecte ja funciona amb `slug` i retorna 404 si no existeix.

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

---

## Distinció d'estat

- `previstes` són les rutes o vistes que el projecte encara vol incorporar o millorar.
- `actuals` són les que ja es poden veure i mantenir al codi.

Quan un fitxer ja existeix, s'ha de documentar com a realitat del projecte, no com a pla.

## Layouts

Fitxers recomanats:

```text
resources/views/layouts/app.php
resources/views/layouts/header.php
resources/views/layouts/footer.php
```

Objectiu:

- no repetir `<html>`, `<head>`, `<body>`, header i footer a cada pàgina;
- tenir una estructura visual comuna;
- facilitar canvis globals de disseny.

## Nota important per a assets públics

Els fitxers JavaScript i CSS que la web ha de carregar des del navegador han d’estar dins de `public/assets/`.
Si un script es posa a `assets/` fora de `public/`, el navegador no el veurà i les interaccions del panell (per exemple, el botó Editar) deixaran de funcionar.

---

## Vistes públiques

Carpeta:

```text
resources/views/public/
```

Fitxers previstos:

```text
home.php
projects.php
project-detail.php
```

Funció:

```text
home.php           → pàgina inicial
projects.php       → llistat de projectes
project-detail.php → detall genèric d’un projecte
```

## Model recomanat de visibilitat

La vista de projecte hauria de ser única i mostrar seccions diferents segons el context d'accés.

Orientació recomanada:

- visitant: només informació pública i organitzativa;
- alumnat: tasques assignades, bastides, ajudes i dades d'aula quan toqui;
- professorat visitant: programacions i visió general, però no dades sensibles ni deadlines;
- professorat que imparteix: contingut complet del projecte;
- administració: tot.

Si cal mostrar més o menys informació, és preferible condicionar blocs dins la mateixa vista abans que crear fitxers separats per rol.

---

## Vistes d’autenticació

Carpeta:

```text
resources/views/auth/
```

Fitxers previstos:

```text
login.php
forgot-password.php
```

---

## Vistes d’alumnes

Carpeta:

```text
resources/views/students/
```

Fitxers previstos:

```text
dashboard.php
projects.php
materials.php
rubrics.php
```

---

## Vistes de professorat

Carpeta:

```text
resources/views/teachers/
```

Fitxers previstos:

```text
dashboard.php
groups.php
students.php
projects.php
rubrics.php
grades.php
```

---

## Vistes d’administració

Carpeta:

```text
resources/views/admin/
```

Fitxers previstos:

```text
dashboard.php
users.php
projects.php
google-sources.php
sync.php
logs.php
settings.php
```

---

## Projectes

No crear fitxers com:

```text
projectes/agroparc.php
projectes/liquencity.php
projectes/projecte-rius.php
```

Fer servir una sola vista:

```text
resources/views/public/project-detail.php
```

I carregar el projecte segons el `slug`.

Exemples de slug:

```text
projecte-rius
mat-penedes
agroparc
projecte-orenetes
liquencity
vespa-velutina
```

---

## Controladors relacionats

Controladors previstos:

```text
PublicController.php
AuthController.php
StudentController.php
TeacherController.php
AdminController.php
```

Exemple de responsabilitat:

```text
PublicController → home, llistat de projectes, detall de projecte
AuthController   → login, logout
StudentController → dashboard d’alumne
TeacherController → dashboard professor
AdminController  → dashboard admin
```

---

## Rutes i idioma

L’idioma principal és:

```text
ca
```

Rutes públiques recomanades:

```text
/ca/projectes
/ca/projectes/{slug}
```

Més endavant es podrà ampliar a:

```text
/es/projectes
/en/projectes
```

---

## 404

Si una ruta no existeix, mostrar una pàgina 404.

Si un projecte no existeix, també mostrar 404.

No mostrar errors interns al visitant.

---

## Sortida HTML segura

Totes les dades que vinguin de base de dades i es mostrin en HTML han d’escapar-se amb:

```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
```

Excepció: contingut HTML validat i controlat, com contingut sincronitzat i sanititzat.

---

## Criteri principal

Una nova pàgina no hauria d’obligar a duplicar capçalera, peu, connexió, permisos ni lògica comuna.

Les vistes mostren dades.

Els controladors preparen dades.

Els models llegeixen i escriuen dades.

Els serveis gestionen lògica complexa.
