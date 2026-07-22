# Skill 04 — Autenticació, rols i seguretat

## Objectiu

Preparar el sistema d’accés, sessions, permisos i seguretat del projecte **Entorns de Natura**.

El projecte gestionarà dades d’alumnat i professorat, per tant la seguretat és una prioritat.

## Estat actual

### Implementat

- login bàsic amb email i contrasenya;
- sessió activa amb `user_id`, `email` i `roles`;
- regeneració d'identificador de sessió en iniciar sessió;
- token CSRF al formulari de login;
- comprovació de rols amb `AuthService::requireRole()`;
- logout i comprovació d'usuari actiu en iniciar sessió;
- fitxa pública de projecte amb bloc contextual de notes per alumnat autenticat.

### Encara previst

- login amb Google;
- CSRF a la resta d'operacions sensibles;
- refinament del context de visibilitat per professorat assignat vs visitant;
- control més fi de seccions privades dins de la fitxa de projecte;
- reforç progressiu de la capa d'auditoria i permisos.

---

## Taules principals

```text
users
web_roles
user_web_roles
project_roles
```

`web_roles` i `user_web_roles` controlen l'accés general a la web. `project_roles` descriu funcions dins d'equips o projectes i no substitueix els permisos web.

---

## Rols disponibles

```text
student
teacher
guest_teacher
coordinator
admin
```

## Model de visibilitat recomanat

Per al futur, convé distingir entre rol d'accés i context de visualització.

Proposta de criteri:

- visitant: accés públic sense sessió, només contingut general;
- student: veu tasques, bastides, ajudes i contingut d'aula autoritzat;
- teacher visitor: veu programacions i informació general del projecte, sense contingut sensible;
- teacher assigned: veu el contingut complet, incloent deadlines i materials interns;
- admin: veu tota la informació.

La implementació recomanada és una sola vista per projecte amb blocs condicionats per context, no pantalles duplicades per perfil.

---

## Jerarquia conceptual

```text
admin       → també pot actuar com a coordinator i teacher
coordinator → també pot actuar com a teacher
teacher     → professor
student     → alumne
```

A la base de dades és preferible assignar rols web explícits a `user_web_roles`.

Exemples:

```text
Oriol Torrents → admin + coordinator + teacher
Oriol Rovira   → coordinator + teacher
Àlex Martí     → teacher
Aiman          → student
Sílvia         → student
```

---

## Login

El login bàsic amb email i contrasenya ja està implementat.

Més endavant es preveu login amb Google.

Camps importants de `users`:

```text
email
google_id
password_hash
is_active
last_login_at
```

---

## Contrasenyes

No guardar mai contrasenyes en text pla.

Per crear hash:

```php
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
```

Per verificar:

```php
$isValid = password_verify($password, $user['password_hash']);
```

Els usuaris que només accedeixin amb Google poden tenir:

```text
password_hash = NULL
```

---

## Login amb Google

El login amb Google encara no està implementat.

Quan s’implementi:

- no posar claus de Google al JavaScript;
- no publicar secrets;
- guardar `google_id` a `users`;
- continuar fent servir `user_web_roles` per decidir permisos web;
- no confiar només en el correu sense validar-lo correctament.

---

## Sessions

Les zones privades han de requerir sessió:

```text
/alumne
/professor
/admin
```

La sessió ha de guardar com a mínim:

```text
user_id
email
roles
```

Cookies de sessió actuals:

```text
lifetime = 0
path = /
secure = true només sota HTTPS
httponly = true
samesite = Lax
```

El sistema regenera l'identificador de sessió en iniciar sessió. Ara mateix, `AuthService::check()` comprova la sessió guardada, però no revalida a cada petició si l'usuari continua actiu o si li han canviat els rols.

---

## CSRF

El token CSRF està implementat al formulari de login.

No s'ha de documentar com a protecció general del sistema: les altres operacions sensibles encara necessiten reforç específic.

---

## Control d’accés

Regles mínimes:

```text
/alumne   → requereix student
/professor → requereix teacher
/admin    → requereix admin
```

Com que admin i coordinator tenen rols múltiples explícits, el codi pot comprovar rols simples.

Exemple:

```text
Oriol Torrents té admin + coordinator + teacher
```

Per tant pot accedir a:

```text
/admin
/professor
```

---

## Usuaris actius

El camp:

```text
is_active
```

ha de controlar si un usuari pot accedir.

Si `is_active = 0`, l’usuari no pot iniciar sessió. Aquesta comprovació es fa durant el login; la sessió existent no es revalida automàticament en cada petició.

---

## Dades sensibles

No exposar públicament:

- notes;
- observacions d’aula;
- dades personals d’alumnes;
- dades internes de professorat;
- logs;
- configuració;
- informació de Google Sources privada.
- programacions internes si no correspon al context;
- deadlines i materials docents si el rol no ho permet.

---

## Sortida HTML segura

Fer servir:

```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
```

per mostrar dades en HTML.

---

## SQL segur

Fer servir sempre consultes preparades amb PDO.

No fer:

```php
$sql = "SELECT * FROM users WHERE email = '$email'";
```

Fer:

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
```

---

## Fitxers sensibles

No publicar:

```text
.env
credencials de Google
fitxers de configuració privada
logs interns
```

Fitxer temporal a eliminar o protegir:

```text
public/test-db.php
```

Estat actual: `public/test-db.php` continua dins del document root públic i s'ha de considerar una mancança pendent de protecció o retirada.

---

## Errors

En desenvolupament local es poden veure errors per depurar.

En producció:

- no mostrar errors interns;
- registrar errors a logs;
- mostrar missatge genèric a l’usuari.

---

## Logs

Els logs han d’anar a:

```text
storage/logs/
```

No han d’estar dins `public/`.

---

## Canvis destructius

No fer sense confirmació explícita:

```text
DROP DATABASE
DROP TABLE
TRUNCATE TABLE
DELETE sense WHERE
eliminar usuaris
eliminar rols
eliminar dades d’avaluació
```

---

## Mancances conegudes

Aquests punts formen part del backlog de seguretat. No s'han de presentar com a resolts:

- les operacions `POST` d'administració encara no tenen CSRF generalitzat;
- la sincronització manual de documents a `/admin/sync-documents` no valida CSRF;
- `/logout` funciona mitjançant ruta accessible per GET;
- no hi ha rate limiting d'intents de login;
- les sessions no es revaliden automàticament després de canviar rols o desactivar usuaris;
- `public/test-db.php` és accessible dins de `public/` mentre no es protegeixi o retiri;
- la política d'analítica, retenció, anonimització i exclusió de rutes sensibles encara no està definida.

---

## Criteri principal

La seguretat s’ha de decidir al servidor, no al navegador.

JavaScript pot millorar l’experiència d’usuari, però no pot ser responsable de protegir dades privades.

El servidor PHP ha de comprovar sempre sessió i permisos.
