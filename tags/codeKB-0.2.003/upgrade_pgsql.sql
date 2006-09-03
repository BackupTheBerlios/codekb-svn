--
-- MySQL upgrade
--

-- Adding timestamps to files

SET client_encoding = 'SQL_ASCII';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

ALTER TABLE ONLY public.files ADD COLUMN created timestamp without time zone;
ALTER TABLE ONLY public.files ADD COLUMN modified timestamp without time zone;

UPDATE public.files SET created = now();

ALTER TABLE ONLY public.files ALTER COLUMN created SET NOT NULL;
