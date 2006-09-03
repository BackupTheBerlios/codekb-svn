--
-- PostgreSQL database dump
--

SET client_encoding = 'SQL_ASCII';
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

ALTER TABLE ONLY public.rights DROP CONSTRAINT rights_groupid_fkey;
ALTER TABLE ONLY public.rights DROP CONSTRAINT rights_category_fkey;
ALTER TABLE ONLY public.group_user DROP CONSTRAINT group_user_userid_fkey;
ALTER TABLE ONLY public.group_user DROP CONSTRAINT group_user_groupid_fkey;
ALTER TABLE ONLY public.files DROP CONSTRAINT symbols_fkey;
ALTER TABLE ONLY public.files DROP CONSTRAINT files_entry_fkey;
ALTER TABLE ONLY public.entry_cat DROP CONSTRAINT entry_cat_entry_fkey;
ALTER TABLE ONLY public.entry_cat DROP CONSTRAINT entry_cat_cat_fkey;
ALTER TABLE ONLY public.categories DROP CONSTRAINT categories_parent_fkey;
ALTER TABLE ONLY public.users DROP CONSTRAINT users_pkey;
ALTER TABLE ONLY public.users DROP CONSTRAINT users_name_key;
ALTER TABLE ONLY public.rights DROP CONSTRAINT rights_pkey;
ALTER TABLE ONLY public.groups DROP CONSTRAINT groups_pkey;
ALTER TABLE ONLY public.groups DROP CONSTRAINT groups_name_key;
ALTER TABLE ONLY public.group_user DROP CONSTRAINT group_user_pkey;
ALTER TABLE ONLY public.files DROP CONSTRAINT files_pkey;
ALTER TABLE ONLY public.files DROP CONSTRAINT files_fs_name_key;
ALTER TABLE ONLY public.entry_cat DROP CONSTRAINT entry_cat_pkey;
ALTER TABLE ONLY public.entries DROP CONSTRAINT entries_pkey;
ALTER TABLE ONLY public.entries DROP CONSTRAINT symbols_fkey;
ALTER TABLE ONLY public.categories DROP CONSTRAINT categories_pkey;
ALTER TABLE ONLY public.symbols DROP CONSTRAINT symbols_pkey;
DROP TRIGGER entries_fti_trigger ON public.entries;
DROP INDEX public.entries_id_idx;
DROP INDEX public.entries_fti_string_idx;
DROP INDEX public.entries_fti_id_idx;
DROP FUNCTION public.fti();
DROP TABLE public.users;
DROP TABLE public.rights;
DROP TABLE public.groups;
DROP TABLE public.group_user;
DROP TABLE public.files;
DROP TABLE public.symbols;
DROP TABLE public.entry_cat;
DROP TABLE public.entries;
DROP TABLE public.entries_fti;
DROP TABLE public.categories;
DROP SCHEMA public;

CREATE SCHEMA public;

COMMENT ON SCHEMA public IS 'Standard public schema';

CREATE FUNCTION fti() RETURNS "trigger"
    AS '$libdir/fti.so', 'fti'
    LANGUAGE c;

CREATE TABLE entries_fti (
    string text,
    id oid
);

CREATE TABLE categories (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    parent integer
);

CREATE TABLE entries (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    symbol character varying(255),
    author character varying(255),
    description character varying(255),
    documentation text,
    created timestamp without time zone NOT NULL,
    modified timestamp without time zone
);

CREATE TABLE entry_cat (
    cat integer NOT NULL,
    entry integer NOT NULL
);

CREATE TABLE files (
    id integer NOT NULL,
    entry integer,
    name character varying(255),
    fs_name character varying(255),
    size integer,
    symbol character varying(255),
    highlight character varying(255),
    created timestamp without time zone NOT NULL,
    modified timestamp without time zone
);

CREATE TABLE group_user (
    groupid integer NOT NULL,
    userid integer NOT NULL
);

CREATE TABLE groups (
    id integer NOT NULL,
    name character varying(255)
);

CREATE TABLE rights (
    groupid integer NOT NULL,
    category integer NOT NULL,
    rights smallint
);

CREATE TABLE symbols (
    name character varying(255) NOT NULL,
    symbol character varying(255) NOT NULL
);

CREATE TABLE users (
    id integer NOT NULL,
    name character varying(255),
    pass character varying(255)
);

COPY categories (id, name, description, parent) FROM stdin;
0	root	\N	\N
\.

COPY entries (id, name, symbol, author, description, documentation, created, modified) FROM stdin;
\.

COPY entry_cat (cat, entry) FROM stdin;
\.

COPY files (id, entry, name, fs_name, size, symbol, highlight, created, modified) FROM stdin;
\.

COPY group_user (groupid, userid) FROM stdin;
0	0
\.

COPY groups (id, name) FROM stdin;
0	\N
\.

COPY rights (groupid, category, rights) FROM stdin;
0	0	255
\.

COPY symbols (name, symbol) FROM stdin;
category	category.png
delete	delete.png
configure	configure.png
newentry	newentry.png
newcat	newcat.png
Binary	type_binary.png
C	type_c.png
Code	type_code.png
CPP	type_cpp.png
Documentation	type_docu.png
Unkown	type_empty.png
Encrypted	type_encrypted.png
Header	type_h.png
HTML	type_html.png
Image	type_img.png
Info	type_info.png
Java	type_java.png
Log	type_log.png
Makefile	type_make.png
PDF	type_pdf.png
Perl	type_perl.png
PHP	type_php.png
Postscript	type_ps.png
Python	type_py.png
Shellscript	type_shell.png
Tarball	type_tar.png
Tex	type_tex.png
Text	type_txt.png
Web	type_web.png
Windows	type_win.png
files	files.png
links	links.png
user	user.png
group	group.png
lock	lock.png
help	help.png
search	search.png
\.

COPY users (id, name, pass) FROM stdin;
0	\N	\N
\.

ALTER TABLE ONLY categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);

ALTER TABLE ONLY entries
    ADD CONSTRAINT entries_pkey PRIMARY KEY (id);

ALTER TABLE ONLY entry_cat
    ADD CONSTRAINT entry_cat_pkey PRIMARY KEY (entry, cat);

ALTER TABLE ONLY files
    ADD CONSTRAINT files_fs_name_key UNIQUE (fs_name);

ALTER TABLE ONLY files
    ADD CONSTRAINT files_pkey PRIMARY KEY (id);

ALTER TABLE ONLY group_user
    ADD CONSTRAINT group_user_pkey PRIMARY KEY (groupid, userid);

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_name_key UNIQUE (name);

ALTER TABLE ONLY groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);

ALTER TABLE ONLY rights
    ADD CONSTRAINT rights_pkey PRIMARY KEY (groupid, category);

ALTER TABLE ONLY symbols
    ADD CONSTRAINT symbols_pkey PRIMARY KEY (name);

ALTER TABLE ONLY users
    ADD CONSTRAINT users_name_key UNIQUE (name);

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

ALTER TABLE ONLY categories
    ADD CONSTRAINT categories_parent_fkey FOREIGN KEY (parent) REFERENCES categories(id);

ALTER TABLE ONLY entry_cat
    ADD CONSTRAINT entry_cat_cat_fkey FOREIGN KEY (cat) REFERENCES categories(id);

ALTER TABLE ONLY entry_cat
    ADD CONSTRAINT entry_cat_entry_fkey FOREIGN KEY (entry) REFERENCES entries(id);

ALTER TABLE ONLY files
    ADD CONSTRAINT files_entry_fkey FOREIGN KEY (entry) REFERENCES entries(id);

ALTER TABLE ONLY group_user
    ADD CONSTRAINT group_user_groupid_fkey FOREIGN KEY (groupid) REFERENCES groups(id);

ALTER TABLE ONLY group_user
    ADD CONSTRAINT group_user_userid_fkey FOREIGN KEY (userid) REFERENCES users(id);

ALTER TABLE ONLY rights
    ADD CONSTRAINT rights_category_fkey FOREIGN KEY (category) REFERENCES categories(id);

ALTER TABLE ONLY rights
    ADD CONSTRAINT rights_groupid_fkey FOREIGN KEY (groupid) REFERENCES groups(id);

ALTER TABLE ONLY files
    ADD CONSTRAINT symbols_fkey FOREIGN KEY (symbol) REFERENCES symbols(name);

ALTER TABLE ONLY entries
    ADD CONSTRAINT symbols_fkey FOREIGN KEY (symbol) REFERENCES symbols(name);

CREATE INDEX entries_fti_id_idx ON entries_fti USING btree (id);

CREATE INDEX entries_fti_string_idx ON entries_fti USING btree (string);

CREATE INDEX entries_id_idx ON entries USING btree (id);

CREATE TRIGGER entries_fti_trigger
    AFTER INSERT OR DELETE OR UPDATE ON entries
    FOR EACH ROW
    EXECUTE PROCEDURE fti('entries_fti', 'name', 'description', 'documentation');

-- In case you're using an unprivileged db user you have to grant these rights
-- Replace 'user' by the name of your db user

-- GRANT USAGE ON SCHEMA public TO user;

-- GRANT SELECT, INSERT, DELETE, UPDATE 
-- 	ON TABLE categories, entries, entries_fti, entry_cat, files, group_user, groups, rights, symbols, users
--	TO user;


--
-- PostgreSQL database dump complete
--

