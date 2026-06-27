-- Tipus de rol de l'aplicació
create type public.app_role as enum ('guest', 'student', 'teacher', 'admin');

-- Perfils vinculats als usuaris de Supabase Auth
create table public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  email text,
  full_name text,
  role public.app_role not null default 'student',
  active boolean not null default true,
  created_at timestamptz default now()
);

-- Projectes de l'assignatura
create table public.projects (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  trimester integer,
  description text,
  order_index integer default 0,
  visible boolean default true,
  created_at timestamptz default now()
);

-- Recursos associats als projectes
create table public.resources (
  id uuid primary key default gen_random_uuid(),
  project_id uuid references public.projects(id) on delete cascade,
  title text not null,
  type text,
  content text,
  url text,
  visibility public.app_role not null default 'guest',
  order_index integer default 0,
  status text not null default 'published'
    check (status in ('draft', 'published', 'archived')),
  created_at timestamptz default now()
);

-- Funció per convertir rols en jerarquia
create or replace function public.role_rank(role public.app_role)
returns integer
language sql
immutable
as $$
  select case role
    when 'guest' then 0
    when 'student' then 1
    when 'teacher' then 2
    when 'admin' then 3
  end;
$$;

-- Funció per saber el rol actual de l'usuari
create or replace function public.current_app_role()
returns public.app_role
language sql
security definer
set search_path = public
as $$
  select coalesce(
    (
      select p.role
      from public.profiles p
      where p.id = auth.uid()
      and p.active = true
      limit 1
    ),
    'guest'::public.app_role
  );
$$;

-- Crear perfil automàtic quan es crea un usuari a Auth
create or replace function public.handle_new_user()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  insert into public.profiles (id, email, full_name, role)
  values (
    new.id,
    new.email,
    coalesce(new.raw_user_meta_data->>'full_name', ''),
    'student'
  );

  return new;
end;
$$;

create trigger on_auth_user_created
after insert on auth.users
for each row execute function public.handle_new_user();

-- Activar Row Level Security
alter table public.profiles enable row level security;
alter table public.projects enable row level security;
alter table public.resources enable row level security;

-- Polítiques de profiles
create policy "Users can read own profile"
on public.profiles
for select
using (id = auth.uid());

create policy "Admins can read all profiles"
on public.profiles
for select
using (public.current_app_role() = 'admin');

create policy "Admins can update profiles"
on public.profiles
for update
using (public.current_app_role() = 'admin');

-- Polítiques de projects
create policy "Anyone can read visible projects"
on public.projects
for select
using (visible = true);

create policy "Admins can manage projects"
on public.projects
for all
using (public.current_app_role() = 'admin')
with check (public.current_app_role() = 'admin');

-- Polítiques de resources
create policy "Users can read resources by role"
on public.resources
for select
using (
  status = 'published'
  and public.role_rank(public.current_app_role()) >= public.role_rank(visibility)
  and exists (
    select 1
    from public.projects p
    where p.id = resources.project_id
    and p.visible = true
  )
);

create policy "Admins can manage resources"
on public.resources
for all
using (public.current_app_role() = 'admin')
with check (public.current_app_role() = 'admin');
