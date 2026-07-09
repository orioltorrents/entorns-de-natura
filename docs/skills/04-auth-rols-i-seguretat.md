# Skill 04 — Autenticació, rols i seguretat

## Objectiu

Preparar el sistema d’accés, sessions, permisos i seguretat del projecte **Entorns de Natura**.

El projecte gestionarà dades d’alumnat i professorat, per tant la seguretat és una prioritat.

---

## Taules principals

```text
users
roles
user_roles
```

---

## Rols previstos

```text
student
teacher
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

A la base de dades és preferible assignar rols explícits.

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

Inicialment es pot crear login bàsic amb email i contrasenya.

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

Encara no està implementat.

Quan s’implementi:

- no posar claus de Google al JavaScript;
- no publicar secrets;
- guardar `google_id` a `users`;
- continuar fent servir `user_roles` per decidir permisos;
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

Si `is_active = 0`, l’usuari no hauria de poder iniciar sessió.

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

## Criteri principal

La seguretat s’ha de decidir al servidor, no al navegador.

JavaScript pot millorar l’experiència d’usuari, però no pot ser responsable de protegir dades privades.

El servidor PHP ha de comprovar sempre sessió i permisos.
