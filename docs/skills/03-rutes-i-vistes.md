# Skill 03 — Rutes i vistes

## Objectiu

Organitzar les rutes i les vistes del projecte **Entorns de Natura** sense crear fitxers PHP solts per cada pàgina.

El projecte ha de funcionar com una aplicació PHP modular.

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