--
-- MySQL database dump
--

DROP TABLE users;
DROP TABLE rights;
DROP TABLE groups;
DROP TABLE group_user;
DROP TABLE files;
DROP TABLE symbols;
DROP TABLE entry_cat;
DROP TABLE entries;
DROP TABLE categories;

CREATE TABLE `categories` (
    `id` int(20) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` varchar(255),
    `parent` int(20),
    PRIMARY KEY (`id`)
);

CREATE TABLE `entries` (
    `id` int(20) NOT NULL,
    `name` varchar(255) NOT NULL,
    `symbol` varchar(255),
    `author` varchar(255),
    `description` varchar(255),
    `documentation` text,
    `created` timestamp,
    `modified` timestamp,
    PRIMARY KEY (`id`)
);

-- ALTER TABLE `entries` ADD FULLTEXT(`description`, `documentation`);

CREATE TABLE `entry_cat` (
    `cat` int(20) NOT NULL,
    `entry` int(20) NOT NULL,
    PRIMARY KEY (`cat`, `entry`)
);

CREATE TABLE `files` (
    `id` int(20) NOT NULL,
    `entry` int(20),
    `name` varchar(255),
    `fs_name` varchar(255),
    `size` int(20),
    `symbol` varchar(255),
    `highlight` varchar(255),
    `created` timestamp,
    `modified` timestamp,
    PRIMARY KEY (`id`)
);

CREATE TABLE `group_user` (
    `groupid` int(20) NOT NULL,
    `userid` int(20) NOT NULL,
    PRIMARY KEY (`groupid`, `userid`)
);

CREATE TABLE `groups` (
    `id` int(20) NOT NULL,
    `name` varchar(255),
    PRIMARY KEY (`id`)
);

CREATE TABLE `rights` (
    `groupid` int(20) NOT NULL,
    `category` int(20) NOT NULL,
    `rights` int(20),
    PRIMARY KEY (`groupid`, `category`)
);

CREATE TABLE `symbols` (
    `name` varchar(255) NOT NULL,
    `symbol` varchar(255) NOT NULL,
    PRIMARY KEY (`name`)
);

CREATE TABLE `users` (
    `id` int(20) NOT NULL,
    `name` varchar(255),
    `pass` varchar(255),
    PRIMARY KEY (`id`)
);

INSERT INTO `categories` (`id`, `name`, `description`, `parent`) VALUES (0, 'root', null, null);

INSERT INTO `group_user` (`groupid`, `userid`) VALUES (0, 0);

INSERT INTO `groups` (`id`, `name`) VALUES (0, null);

INSERT INTO `rights` (`groupid`, `category`, `rights`) VALUES (0, 0, 255);

INSERT INTO `symbols` (`name`, `symbol`) VALUES ('category', 'category.png'), ('delete', 'delete.png'), ('configure', 'configure.png'), ('newentry', 'newentry.png'), ('newcat', 'newcat.png'), ('Binary', 'type_binary.png'), ('C', 'type_c.png'), ('Code', 'type_code.png'), ('CPP', 'type_cpp.png'), ('Documentation', 'type_docu.png'), ('Unkown', 'type_empty.png'), ('Encrypted', 'type_encrypted.png'), ('Header', 'type_h.png'), ('HTML', 'type_html.png'), ('Image', 'type_img.png'), ('Info', 'type_info.png'), ('Java', 'type_java.png'), ('Log', 'type_log.png'), ('Makefile', 'type_make.png'), ('PDF', 'type_pdf.png'), ('Perl', 'type_perl.png'), ('PHP', 'type_php.png'), ('Postscript', 'type_ps.png'), ('Python', 'type_py.png'), ('Shellscript', 'type_shell.png'), ('Tarball', 'type_tar.png'), ('Tex', 'type_tex.png'), ('Text', 'type_txt.png'), ('Web', 'type_web.png'), ('Windows', 'type_win.png'), ('files', 'files.png'), ('links', 'links.png'), ('user', 'user.png'), ('group', 'group.png'), ('lock', 'lock.png'), ('help', 'help.png'), ('search', 'search.png');

INSERT INTO `users` (`id`, `name`, `pass`) VALUES (0, null, null);

--
-- MySQL database dump complete
--

