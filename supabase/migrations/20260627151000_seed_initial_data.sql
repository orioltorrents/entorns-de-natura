insert into public.projects (slug, title, trimester, description, order_index)
values
('projecte-rius', 'Projecte Rius', 1, 'Diagnosi ambiental d’un tram fluvial proper.', 1),
('eia', 'Estudi d’Impacte Ambiental', 2, 'Estudi ambiental simplificat sobre una actuació humana.', 2),
('liquencity', 'LiquenCity', 3, 'Projecte de líquens com a bioindicadors de qualitat de l’aire.', 3),
('vespa-velutina', 'Vespa velutina', 3, 'Estudi d’una espècie invasora i el seu impacte ambiental.', 4),
('orenetes', 'Projecte Orenetes', 3, 'Cens de nius i estudi de biodiversitat urbana.', 5);

-- Recursos públics
insert into public.resources (project_id, title, type, content, visibility, order_index)
select id, 'Presentació del Projecte Rius', 'text',
'Introducció pública al Projecte Rius.', 'guest', 1
from public.projects
where slug = 'projecte-rius';

-- Recursos per alumnat
insert into public.resources (project_id, title, type, url, visibility, order_index)
select id, 'Dossier de camp Projecte Rius', 'document',
'https://drive.google.com/pendent', 'student', 2
from public.projects
where slug = 'projecte-rius';

-- Recursos només professorat
insert into public.resources (project_id, title, type, url, visibility, order_index)
select id, 'Rúbrica docent Projecte Rius', 'rubrica',
'https://drive.google.com/pendent', 'teacher', 3
from public.projects
where slug = 'projecte-rius';