-- Tancar funcions SECURITY DEFINER que no han de quedar exposades via RPC.
--
-- Supabase exposa les funcions del schema public a /rest/v1/rpc/<nom_funcio>
-- quan el rol té permís EXECUTE. PostgreSQL dona EXECUTE a PUBLIC per defecte,
-- per això convé revocar-lo explícitament en funcions administratives.

do $$
begin
  if to_regprocedure('public.rls_auto_enable()') is not null then
    revoke execute on function public.rls_auto_enable() from public;
    revoke execute on function public.rls_auto_enable() from anon;
    revoke execute on function public.rls_auto_enable() from authenticated;
  end if;
end $$;

-- Aquesta funció només s'ha d'executar com a trigger intern de Supabase Auth.
revoke execute on function public.handle_new_user() from public;
revoke execute on function public.handle_new_user() from anon;
revoke execute on function public.handle_new_user() from authenticated;

-- Aquesta funció sí que s'usa dins les policies RLS per calcular el rol actual.
-- La deixem disponible per a anon i authenticated de manera explícita.
revoke execute on function public.current_app_role() from public;
grant execute on function public.current_app_role() to anon;
grant execute on function public.current_app_role() to authenticated;
