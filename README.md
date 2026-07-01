# Entorns de Natura

Web del projecte **Entorns de Natura de 4t d'ESO**.

L'objectiu és construir un espai públic i ordenat per consultar projectes, recursos, materials curriculars i eines docents, amb diferents nivells de visibilitat segons el rol de cada usuari.

Web publicada a GitHub Pages:

https://orioltorrents.github.io/entorns-de-natura/

## Objectiu del projecte

Aquest repositori vol servir com a base per a una web que pugui ser útil per a:

- alumnat que cursa Entorns de Natura
- professorat que imparteix la matèria
- professorat que vol recuperar eines, rúbriques o materials
- públic general interessat en el projecte

La idea de fons és separar bé:

```text
Frontend estàtic = GitHub Pages
Backend i dades = Supabase
Permisos = Row Level Security
```

## Estat actual

El projecte ara mateix inclou:

- una primera maqueta web estàtica
- una estructura visual per mostrar projectes i recursos
- un selector de rol per simular què veu cada tipus d'usuari
- migracions inicials de Supabase
- taules per a projectes, recursos i perfils
- policies RLS per filtrar recursos segons rol

Encara falta connectar el frontend amb Supabase de manera real.

## Rols previstos

Els rols inicials són:

| Rol | Ús previst |
| --- | --- |
| `guest` | Públic general sense iniciar sessió |
| `student` | Alumnat |
| `teacher` | Professorat |
| `admin` | Gestió del projecte |

La jerarquia és:

```text
guest < student < teacher < admin
```

Això vol dir que un recurs visible per a `student` també el poden veure `teacher` i `admin`.

## Estructura del repositori

```text
entorns-de-natura/
├── assets/
│   └── hero-entorns-natura.png
├── css/
│   └── styles.css
├── supabase/
│   └── migrations/
│       ├── 20260627150000_initial_schema.sql
│       ├── 20260627151000_seed_initial_data.sql
│       └── 20260627152000_lock_down_security_definer_functions.sql
├── index.html
└── README.md
```

## Supabase

La base de dades inicial inclou:

- `profiles`: perfils vinculats a Supabase Auth
- `projects`: projectes de l'assignatura
- `resources`: recursos associats als projectes

També inclou el tipus:

```sql
public.app_role as enum ('guest', 'student', 'teacher', 'admin')
```

I funcions de suport com:

- `role_rank(role)`
- `current_app_role()`
- `handle_new_user()`

## Seguretat

El projecte fa servir **Row Level Security** a Supabase.

La taula `resources` filtra els recursos segons:

- estat publicat
- rol mínim necessari
- projecte visible

També hi ha una migració per tancar funcions `SECURITY DEFINER` que no han de quedar exposades públicament via RPC.

Important:

- la `anon key` de Supabase pot anar al frontend
- la `service_role key` no s'ha de posar mai al frontend ni a GitHub
- la seguretat real ha de quedar definida a les policies RLS

## GitHub Pages

La web es pot publicar directament amb GitHub Pages perquè és un frontend estàtic.

Configuració recomanada:

- Source: `Deploy from a branch`
- Branch: `main`
- Folder: `/root`

URL pública:

```text
https://orioltorrents.github.io/entorns-de-natura/
```

Quan s'activi autenticació amb Supabase, caldrà afegir aquesta URL a:

```text
Supabase > Authentication > URL Configuration
```

Com a `Site URL` i també com a `Redirect URL`.

## Full de ruta

Possibles següents passos:

- connectar `index.html` amb Supabase
- substituir les dades simulades per consultes reals
- afegir login amb Supabase Auth
- mostrar el perfil i rol de l'usuari connectat
- crear una vista pública de projectes
- crear una vista d'alumnat
- crear una vista de professorat
- preparar una zona d'administració
- afegir més taules curriculars: sabers, competències i criteris
- documentar el procés de manteniment de continguts

## Desenvolupament local

Com que ara mateix és una web estàtica, es pot obrir directament:

```text
index.html
```

Si més endavant s'afegeixen mòduls JavaScript o eines de build, es podrà incorporar un servidor local de desenvolupament.

## Notes de treball

Aquest README és inicial i s'anirà millorant a mesura que el projecte avanci.

Idees pendents per documentar:

- criteris de publicació dels recursos
- com donar d'alta usuaris
- com assignar rols
- com editar projectes i recursos
- relació amb documents de Google Drive
- estructura curricular completa d'Entorns de Natura
